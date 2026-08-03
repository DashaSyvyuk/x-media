<?php

namespace App\Service;

use App\Entity\Feed;
use App\Entity\ProductRozetkaCharacteristicValue;
use App\Entity\RozetkaProduct;
use App\Repository\CategoryFeedPriceRepository;
use App\Repository\CategoryRepository;
use App\Repository\FeedRepository;
use App\Repository\ProductRepository;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class GenerateRozetkaXmlService
{
    use PriceTrait;

    private const FILE_NAME = 'products.xml';
    private const CHUNK_SIZE = 100;

    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository,
        private readonly FeedRepository $feedRepository,
        private readonly CategoryFeedPriceRepository $categoryFeedPriceRepository,
        private readonly BunnyStorageClient $bunny,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly string $projectDir,
        private readonly string $environment,
    ) {
    }

    public function getPublicUrl(string $activeFor = 'active_for_a'): string
    {
        $folder = sprintf('rozetka_for_%s', substr($activeFor, -1));

        return $this->bunny->getPublicUrl($folder, self::FILE_NAME);
    }

    /**
     * Start feed generation in a detached console process so the HTTP request
     * returns immediately (avoids proxy/nginx 503 on long Rozetka A builds).
     */
    public function startBackground(string $activeFor = 'active_for_a'): string
    {
        $feedArg = $activeFor === 'active_for_p' ? 'rozetka-p' : 'rozetka-a';
        $publicUrl = $this->getPublicUrl($activeFor);

        if (! $this->canSpawnBackgroundProcess()) {
            $this->logger->warning(
                'Cannot spawn background process for Rozetka XML; running inline.',
                ['feed' => $feedArg],
            );
            $this->execute($activeFor);

            return $publicUrl;
        }

        $console = $this->projectDir . '/bin/console';
        $php = $this->resolvePhpCliBinary();
        $logFile = $this->projectDir . '/var/log/rozetka-feed-' . $feedArg . '.log';

        if (! is_dir(dirname($logFile))) {
            @mkdir(dirname($logFile), 0775, true);
        }

        // nohup + background shell: survives PHP-FPM request end (Process::__destruct would SIGTERM).
        $command = sprintf(
            'nohup %s %s app:generate-feed %s --env=%s --no-interaction >> %s 2>&1 &',
            escapeshellarg($php),
            escapeshellarg($console),
            escapeshellarg($feedArg),
            escapeshellarg($this->environment),
            escapeshellarg($logFile),
        );
        exec($command);

        $this->logger->info('Rozetka XML background generation started.', [
            'feed' => $feedArg,
            'php'  => $php,
            'log'  => $logFile,
        ]);

        return $publicUrl;
    }

    private function canSpawnBackgroundProcess(): bool
    {
        if (! \function_exists('exec')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return ! in_array('exec', $disabled, true);
    }

    /**
     * Prefer a real CLI php binary — under php-fpm, PHP_BINARY is often php-fpm itself.
     */
    private function resolvePhpCliBinary(): string
    {
        $candidates = [];

        if (\defined('PHP_BINARY') && PHP_BINARY !== '') {
            $binary = PHP_BINARY;
            $candidates[] = $binary;
            if (str_contains(basename($binary), 'php-fpm')) {
                $dir = dirname($binary);
                $candidates[] = $dir . '/php';
                $candidates[] = dirname($dir) . '/bin/php';
            }
        }

        $which = '';
        if (\function_exists('shell_exec')) {
            $which = trim((string) shell_exec('command -v php 2>/dev/null'));
        }
        if ($which !== '') {
            $candidates[] = $which;
        }

        $candidates[] = '/usr/local/bin/php';
        $candidates[] = '/usr/bin/php';
        $candidates[] = 'php';

        foreach ($candidates as $candidate) {
            if ($candidate === 'php') {
                return $candidate;
            }
            if (is_executable($candidate) && ! str_contains(basename($candidate), 'php-fpm')) {
                return $candidate;
            }
        }

        return 'php';
    }

    public function execute(string $activeFor = 'active_for_a'): ?string
    {
        $this->allowLongRunningProcess();
        @ini_set('memory_limit', '2048M');

        $activeForInCamelCase = lcfirst(str_replace('_', '', ucwords($activeFor, '_')));
        $categories = $this->categoryRepository->getCategoriesForRozetka($activeForInCamelCase);
        $productIds = $this->productRepository->getProductIdsForRozetka($activeForInCamelCase);
        $feed = $this->feedRepository->findOneBy(['type' => Feed::FEED_ROZETKA]);
        $ignoredBrands = $this->getIgnoredBrandsLookup($feed);
        $cutCharacteristics = (bool) ($feed?->getCutCharacteristics() ?? false);
        $priceParametersByCategoryId = $feed
            ? $this->categoryFeedPriceRepository->findByFeedIndexedByCategoryId($feed)
            : [];
        // Snapshot scalars before clear(); price-parameter entities stay readable when detached.
        $folder    = sprintf('rozetka_for_%s', substr($activeFor, -1));
        $localPath = $this->projectDir . '/public/' . $folder . '/' . self::FILE_NAME;
        $localDir  = dirname($localPath);

        if (! is_dir($localDir)) {
            @mkdir($localDir, 0775, true);
        }

        try {
            $writer = new \XMLWriter();
            if (! $writer->openUri($localPath)) {
                return null;
            }

            $writer->startDocument('1.0', 'UTF-8');
            $writer->setIndent(true);
            $writer->startElement('yml_catalog');
            $writer->writeAttribute('date', Carbon::now()->format('Y-m-d H:i'));
            $writer->startElement('shop');
            $writer->writeElement('name', 'X-media');
            $writer->writeElement('company', 'X-media');
            $writer->writeElement('url', 'https://x-media.com.ua/');
            $writer->startElement('currencies');
            $writer->startElement('currency');
            $writer->writeAttribute('id', 'UAH');
            $writer->writeAttribute('rate', '1');
            $writer->endElement();
            $writer->endElement();

            $writer->startElement('categories');
            foreach ($categories as $category) {
                $writer->startElement('category');
                $writer->writeAttribute('id', (string) $category->getId());
                $writer->text((string) $category->getRozetkaCategory());
                $writer->endElement();
            }
            $writer->endElement();
            // Categories are fully written — free them before product chunks.
            $this->entityManager->clear();

            $writer->startElement('offers');
            foreach (array_chunk($productIds, self::CHUNK_SIZE) as $chunkIds) {
                $products = $this->productRepository->getProductsForRozetkaByIds($chunkIds);
                $vendorsByProductId = $this->productRepository->getRozetkaVendorsByProductIds($chunkIds);

                foreach ($products as $product) {
                    /** @var RozetkaProduct $rozetkaProduct */
                    $rozetkaProduct = $product->getRozetka();
                    $vendorValue = $vendorsByProductId[$product->getId()] ?? null;

                    if ($vendorValue === null || $vendorValue === '') {
                        continue;
                    }

                    if (isset($ignoredBrands[$vendorValue])) {
                        continue;
                    }

                    $categoryId = $product->getCategory()->getId();
                    $images = $product->getImages();
                    $characteristics = $rozetkaProduct->getActiveValues();
                    $priceParameters = $priceParametersByCategoryId[$categoryId] ?? null;
                    $promoPrice = $rozetkaProduct->getPromoPrice();
                    $hasPromo   = $rozetkaProduct->getPromoPriceActive() && $promoPrice !== null;

                    $writer->startElement('offer');
                    $writer->writeAttribute('id', (string) $product->getId());
                    $writer->writeAttribute('available', 'true');
                    $writer->writeElement('stock_quantity', (string) ($rozetkaProduct->getStockQuantity() ?: 3));
                    $writer->writeElement('url', sprintf('https://x-media.com.ua/products/%s', $product->getId()));
                    $writer->writeElement(
                        'price',
                        (string) ($rozetkaProduct->getPrice() ?: $this->getPrice($product, $feed, $priceParameters))
                    );
                    $writer->writeElement('old_price', (string) $rozetkaProduct->getCrossedOutPrice());
                    if ($hasPromo) {
                        $writer->writeElement('price_promo', number_format($promoPrice, 0, '.', ''));
                    }
                    $writer->writeElement('currencyId', 'UAH');
                    $writer->writeElement('categoryId', (string) $categoryId);
                    foreach ($images as $image) {
                        $writer->writeElement(
                            'picture',
                            sprintf('https://x-media.com.ua/images/products/%s', $image->getImageUrl())
                        );
                    }
                    $writer->writeElement('vendor', (string) $vendorValue);
                    $writer->writeElement(
                        'name',
                        RozetkaProduct::formatMarketplaceTitle(
                            $rozetkaProduct->getTitle(),
                            $product->getProductCode(),
                        ),
                    );
                    $writer->startElement('description');
                    $writer->writeCdata(trim(strip_tags((string) $rozetkaProduct->getDescription())));
                    $writer->endElement();
                    $writer->writeElement('article', (string) $product->getId());
                    $writer->writeElement('series', (string) $rozetkaProduct->getSeries());

                    /** @var ProductRozetkaCharacteristicValue $characteristic */
                    foreach ($characteristics as $characteristic) {
                        $values = $this->getCharacteristicValue($characteristic);
                        $paramName = $this->convertString(
                            $characteristic->getCharacteristic()?->getTitle() ?? '',
                            $cutCharacteristics,
                        );

                        if (is_array($values)) {
                            $writer->startElement('param');
                            $writer->writeAttribute('name', $paramName);
                            foreach ($values as $value) {
                                $writer->writeElement('value', $value);
                            }
                            $writer->endElement();
                            continue;
                        }

                        $writer->startElement('param');
                        $writer->writeAttribute('name', $paramName);
                        $writer->text($this->convertString($values, $cutCharacteristics));
                        $writer->endElement();
                    }
                    $writer->endElement();
                }

                $writer->flush();
                $this->entityManager->clear();
            }
            $writer->endElement();
            $writer->endElement();
            $writer->endElement();
            $writer->endDocument();
            $writer->flush();

            return $this->bunny->uploadAndGetUrl($localPath, $folder, self::FILE_NAME);
        } catch (\Throwable $e) {
            $this->logger->error('Rozetka XML generation failed.', ['exception' => $e]);

            return null;
        }
    }

    private function convertString(string $text, bool $cutCharacteristics): string
    {
        $text = strip_tags($text);

        if ($cutCharacteristics) {
            $text = mb_substr($text, 0, 255, 'UTF-8');
        }

        return $text;
    }

    /**
     * @return array<string, true>
     */
    private function getIgnoredBrandsLookup(?Feed $feed): array
    {
        if ($feed === null) {
            return [];
        }

        $ignoreBrands = $feed->getIgnoreBrands();
        if ($ignoreBrands === null || trim($ignoreBrands) === '') {
            return [];
        }

        $result = [];
        foreach (explode(';', $ignoreBrands) as $brand) {
            $normalizedBrand = trim($brand);
            if ($normalizedBrand !== '') {
                $result[$normalizedBrand] = true;
            }
        }

        return $result;
    }

    /**
     * @param ProductRozetkaCharacteristicValue $value
     *
     * @return string|array<string>
     */
    private function getCharacteristicValue(ProductRozetkaCharacteristicValue $value): string|array
    {
        $characteristic = $value->getCharacteristic();
        $type = $characteristic?->getType() ?? '';

        return match ($type) {
            'ListValues'          => $value->getValues()->map(fn ($value) => $value->getTitle())->toArray(),
            'CheckBoxGroupValues' => $value->getValues()->map(fn ($value) => $value->getTitle())->toArray(),
            'List'                => implode(
                ',',
                $value->getValues()->map(fn ($value) => $value->getTitle())->toArray()
            ),
            'ComboBox'            => $value->getValue() ? $value->getValue()->getTitle() : '',
            'Integer', 'Decimal', 'TextInput', 'TextArea' => $value->getStringValue(),
            default               => '',
        };
    }
}
