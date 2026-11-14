<?php

namespace App\Service;

use App\Entity\Feed;
use App\Repository\CategoryFeedPriceRepository;
use App\Repository\CategoryRepository;
use App\Repository\FeedRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class GenerateHotlineXmlService
{
    use PriceTrait;

    private const FOLDER = 'hotline';
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
        private readonly string $cdnUrl,
    ) {
    }

    public function getPublicUrl(): string
    {
        return $this->bunny->getPublicUrl(self::FOLDER, self::FILE_NAME);
    }

    /**
     * Start feed generation in a detached console process so the HTTP request
     * returns immediately (avoids proxy/nginx 503 on long Hotline builds).
     */
    public function startBackground(): string
    {
        $publicUrl = $this->getPublicUrl();

        if (! $this->canSpawnBackgroundProcess()) {
            $this->logger->warning(
                'Cannot spawn background process for Hotline XML; running inline.',
            );
            $this->execute();

            return $publicUrl;
        }

        $console = $this->projectDir . '/bin/console';
        $php = $this->resolvePhpCliBinary();
        $logFile = $this->projectDir . '/var/log/hotline-feed.log';

        if (! is_dir(dirname($logFile))) {
            @mkdir(dirname($logFile), 0775, true);
        }

        // nohup + background shell: survives PHP-FPM request end (Process::__destruct would SIGTERM).
        $command = sprintf(
            'nohup %s %s app:generate-feed %s --env=%s --no-interaction >> %s 2>&1 &',
            escapeshellarg($php),
            escapeshellarg($console),
            escapeshellarg('hotline'),
            escapeshellarg($this->environment),
            escapeshellarg($logFile),
        );
        exec($command);

        $this->logger->info('Hotline XML background generation started.', [
            'php' => $php,
            'log' => $logFile,
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

        if (\defined('PHP_BINARY')) {
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

    public function execute(): ?string
    {
        $this->allowLongRunningProcess();
        @ini_set('memory_limit', '2048M');

        $categories = $this->categoryRepository->getCategoriesForHotline();
        $productIds = $this->productRepository->getProductIdsForHotline();
        $feed = $this->feedRepository->findOneBy(['type' => Feed::FEED_HOTLINE]);
        $ignoredBrands = $this->getIgnoredBrandsLookup($feed);
        $cutCharacteristics = (bool) ($feed?->getCutCharacteristics() ?? false);
        $priceParametersByCategoryId = $feed
            ? $this->categoryFeedPriceRepository->findByFeedIndexedByCategoryId($feed)
            : [];
        $localPath = $this->projectDir . '/public/' . self::FOLDER . '/' . self::FILE_NAME;
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
            $writer->startElement('price');
            $writer->writeElement('date', date('Y-m-d H:i:s'));
            $writer->writeElement('firmName', 'X-media');
            $writer->writeElement('firmId', '41387');
            $writer->startElement('delivery');
            $writer->writeAttribute('delivery_id', '1');
            $writer->writeAttribute('type', 'warehouse');
            $writer->writeAttribute('carrier', 'NP');
            $writer->endElement();

            $writer->startElement('categories');
            foreach ($categories as $category) {
                $writer->startElement('category');
                $writer->writeElement('id', (string) $category->getId());
                $writer->writeElement('name', (string) $category->getHotlineCategory());
                $writer->endElement();
            }
            $writer->endElement();
            // Categories are fully written — free them before product chunks.
            $this->entityManager->clear();

            $writer->startElement('items');
            foreach (array_chunk($productIds, self::CHUNK_SIZE) as $chunkIds) {
                $products = $this->productRepository->getProductsForHotlineByIds($chunkIds);
                $vendorsByProductId = $this->productRepository->getVendorsByProductIds($chunkIds);

                foreach ($products as $product) {
                    $vendorValue = $vendorsByProductId[$product->getId()] ?? null;

                    if ($vendorValue === null || $vendorValue === '') {
                        continue;
                    }

                    if (isset($ignoredBrands[$vendorValue])) {
                        continue;
                    }

                    $categoryId = $product->getCategory()->getId();
                    $images = $product->getImages();
                    $characteristics = $product->getCharacteristics();
                    $warranty = array_values(
                        array_filter(
                            $characteristics->toArray(),
                            fn ($item) => $item->getTitle() == 'Гарантія'
                        )
                    );
                    $priceParameters = $priceParametersByCategoryId[$categoryId] ?? null;

                    $writer->startElement('item');
                    $writer->writeElement('id', (string) $product->getId());
                    $writer->writeElement('categoryId', (string) $categoryId);
                    $writer->writeElement('vendor', (string) $vendorValue);
                    $writer->writeElement('name', $this->formatString($product->getTitle(), $cutCharacteristics));
                    $writer->writeElement('description', strip_tags((string) $product->getDescription()));
                    $writer->writeElement('url', sprintf('https://x-media.com.ua/products/%s', $product->getId()));

                    foreach ($images as $image) {
                        $writer->writeElement(
                            'image',
                            rtrim($this->cdnUrl, '/') . '/products/' . $image->getImageUrl()
                        );
                    }

                    $writer->writeElement('priceRUAH', (string) $this->getPrice($product, $feed, $priceParameters));
                    $writer->writeElement('stock', 'В наявності');
                    $writer->startElement('guarantee');
                    $writer->writeAttribute('type', 'manufacturer');
                    $writer->text((string) ($warranty ? (int) $warranty[0]->getValue() : 12));
                    $writer->endElement();

                    foreach ($characteristics as $characteristic) {
                        $writer->startElement('param');
                        $writer->writeAttribute(
                            'name',
                            $this->formatString($characteristic->getTitle(), $cutCharacteristics)
                        );
                        $writer->text($this->formatString($characteristic->getValue(), $cutCharacteristics));
                        $writer->endElement();
                    }

                    $writer->writeElement('condition', '0');
                    $writer->writeElement('code', (string) $product->getProductCode());
                    $writer->endElement();
                }

                $writer->flush();
                $this->entityManager->clear();
            }
            $writer->endElement();
            $writer->endElement();
            $writer->endDocument();
            $writer->flush();

            return $this->bunny->uploadAndGetUrl($localPath, self::FOLDER, self::FILE_NAME);
        } catch (\Throwable $e) {
            $this->logger->error('Hotline XML generation failed.', ['exception' => $e]);

            return null;
        }
    }

    private function formatString(string $text, bool $cutCharacteristics): string
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
}
