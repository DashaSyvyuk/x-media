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

    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository,
        private readonly FeedRepository $feedRepository,
        private readonly CategoryFeedPriceRepository $categoryFeedPriceRepository,
        private readonly BunnyStorageClient $bunny,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function execute(string $activeFor = 'active_for_a'): ?string
    {
        $this->allowLongRunningProcess();

        $activeForInCamelCase = lcfirst(str_replace('_', '', ucwords($activeFor, '_')));
        $categories = $this->categoryRepository->getCategoriesForRozetka($activeForInCamelCase);
        $products = $this->productRepository->getProductsForRozetka($activeForInCamelCase);
        $feed = $this->feedRepository->findOneBy(['type' => Feed::FEED_ROZETKA]);
        $ignoredBrands = $this->getIgnoredBrandsLookup($feed);
        $priceParametersByCategoryId = $feed
            ? $this->categoryFeedPriceRepository->findByFeedIndexedByCategoryId($feed)
            : [];
        $folder    = sprintf('rozetka_for_%s', substr($activeFor, -1));
        $localPath = __DIR__ . '/../../public/' . $folder . '/' . self::FILE_NAME;
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

            $writer->startElement('offers');
            $processed = 0;
            foreach ($products as $product) {
                ++$processed;
                /** @var RozetkaProduct $rozetkaProduct */
                $rozetkaProduct = $product->getRozetka();
                $vendor = array_values(
                    array_filter(
                        $product->getFilterAttributes()->toArray(),
                        fn ($item) => in_array(
                            $item->getFilter()->getTitle(),
                            ['Марка', 'Виробник']
                        )
                    )
                );

                if (empty($vendor)) {
                    if ($processed % 100 === 0) {
                        $this->entityManager->clear();
                        gc_collect_cycles();
                    }
                    continue;
                }

                $vendorValue = $vendor[0]->getFilterAttribute()->getValue();
                if (isset($ignoredBrands[$vendorValue])) {
                    if ($processed % 100 === 0) {
                        $this->entityManager->clear();
                        gc_collect_cycles();
                    }
                    continue;
                }

                $categoryId = $product->getCategory()->getId();
                $images = $product->getImages();
                $characteristics = $rozetkaProduct->getValues();
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
                    sprintf('%s (%s)', strip_tags((string) $rozetkaProduct->getTitle()), $product->getProductCode())
                );
                $writer->startElement('description');
                $writer->writeCdata(trim(strip_tags((string) $rozetkaProduct->getDescription())));
                $writer->endElement();
                $writer->writeElement('article', (string) $product->getId());
                $writer->writeElement('series', (string) $rozetkaProduct->getSeries());

                /** @var ProductRozetkaCharacteristicValue $characteristic */
                foreach ($characteristics as $characteristic) {
                    $values = $this->getCharacteristicValue($characteristic);
                    $paramName = $this->convertString($characteristic->getCharacteristic()?->getTitle() ?? '', $feed);

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
                    $writer->text($this->convertString($values, $feed));
                    $writer->endElement();
                }
                $writer->endElement();

                if ($processed % 100 === 0) {
                    $this->entityManager->clear();
                    gc_collect_cycles();
                }
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

    private function convertString(string $text, ?Feed $feed): string
    {
        $text = strip_tags($text);

        if ($feed && $feed->getCutCharacteristics()) {
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
