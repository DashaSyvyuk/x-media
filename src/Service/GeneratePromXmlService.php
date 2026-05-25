<?php

namespace App\Service;

use App\Entity\Feed;
use App\Repository\CategoryFeedPriceRepository;
use App\Repository\CategoryRepository;
use App\Repository\FeedRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class GeneratePromXmlService
{
    use PriceTrait;

    private const FOLDER = 'prom';
    private const FILE_NAME = 'products.xml';

    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository,
        private readonly FeedRepository $feedRepository,
        private readonly CategoryFeedPriceRepository $categoryFeedPriceRepository,
        private readonly BunnyStorageClient $bunny,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function execute(): ?string
    {
        $this->allowLongRunningProcess();

        $categories = $this->categoryRepository->getCategoriesForProm();
        $products = $this->productRepository->getProductsForProm();
        $feed = $this->feedRepository->findOneBy(['type' => Feed::FEED_PROM]);
        $ignoredBrands = $this->getIgnoredBrandsLookup($feed);
        $priceParametersByCategoryId = $feed
            ? $this->categoryFeedPriceRepository->findByFeedIndexedByCategoryId($feed)
            : [];
        $localPath = __DIR__ . '/../../public/' . self::FOLDER . '/' . self::FILE_NAME;
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
            $writer->startElement('shop');
            $writer->startElement('categories');
            foreach ($categories as $category) {
                $writer->startElement('category');
                $writer->writeAttribute('id', (string) $category['id']);
                $writer->writeAttribute('portal_url', (string) $category['promCategoryLink']);
                $writer->text((string) $category['title']);
                $writer->endElement();
            }
            $writer->endElement();

            $writer->startElement('offers');
            $processed = 0;
            foreach ($products as $product) {
                ++$processed;
                $vendor = array_values(
                    array_filter(
                        $product->getFilterAttributes()->toArray(),
                        fn ($item) => in_array($item->getFilter()->getTitle(), ['Марка', 'Виробник'])
                    )
                );

                if (empty($vendor)) {
                    if ($processed % 100 === 0) {
                        $this->entityManager->clear();
                        gc_collect_cycles();
                    }
                    continue;
                }

                $vendorValue = (string) $vendor[0]->getFilterAttribute()->getValue();
                if ($vendorValue !== '' && isset($ignoredBrands[$vendorValue])) {
                    if ($processed % 100 === 0) {
                        $this->entityManager->clear();
                        gc_collect_cycles();
                    }
                    continue;
                }

                $categoryId = $product->getCategory()->getId();
                $priceParameters = $priceParametersByCategoryId[$categoryId] ?? null;
                $title = $this->formatString((string) $product->getTitle(), $feed);
                $productCode = (string) $product->getProductCode();

                $writer->startElement('offer');
                $writer->writeAttribute('id', (string) $product->getId());
                $writer->writeAttribute('selling_type', 'r');
                $writer->writeAttribute('available', 'true');
                $writer->writeElement('name', sprintf('%s (%s)', $title, $productCode));
                $writer->writeElement('name_ua', sprintf('%s (%s)', $title, $productCode));
                $writer->writeElement('categoryId', (string) $categoryId);
                $writer->writeElement('portal_category_url', (string) $product->getCategory()->getPromCategoryLink());
                $writer->writeElement(
                    'price',
                    (string) $this->getPriceFromValue((int) $product->getPrice(), $priceParameters)
                );
                $writer->writeElement('quantity_in_stock', '10');
                $writer->writeElement('currencyId', 'UAH');

                foreach ($product->getImages() as $index => $image) {
                    if ($index < 10) {
                        $writer->writeElement(
                            'picture',
                            sprintf('https://x-media.com.ua/images/products/%s', $image->getImageUrl())
                        );
                    }
                }

                $writer->writeElement('vendor', mb_substr($vendorValue, 0, 25, 'UTF-8'));
                foreach ($product->getCharacteristics() as $characteristic) {
                    $writer->startElement('param');
                    $writer->writeAttribute('name', $this->formatString($characteristic->getTitle(), $feed));
                    $writer->text($this->formatString($characteristic->getValue(), $feed));
                    $writer->endElement();
                }

                $description = strip_tags((string) $product->getDescription());
                $writer->writeElement('description', $description);
                $writer->writeElement('description_ua', $description);
                $writer->writeElement('article', (string) $product->getProductCode());
                $writer->endElement();

                if ($processed % 100 === 0) {
                    $this->entityManager->clear();
                    gc_collect_cycles();
                }
            }
            $writer->endElement();
            $writer->endElement();
            $writer->endDocument();
            $writer->flush();

            return $this->bunny->uploadAndGetUrl($localPath, self::FOLDER, self::FILE_NAME);
        } catch (\Throwable $e) {
            var_dump('An exception occurred: ' . $e->getMessage());

            return null;
        }
    }

    private function formatString(string $text, ?Feed $feed): string
    {
        $text = strip_tags($text);

        if ($feed && $feed->getCutCharacteristics()) {
            $text = mb_substr($text, 0, 255, 'UTF-8');
        }

        return $text;
    }

    private function allowLongRunningProcess(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        @ini_set('max_execution_time', '0');
        ignore_user_abort(true);
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

    private function getPriceFromValue(int $price, ?\App\Entity\CategoryFeedPrice $priceParameters): float|int
    {
        if ($priceParameters && ($priceParameters->getOurPercent() !== 0 || $priceParameters->getFee() !== 0)) {
            return $this->adjustPrice(
                $price,
                $priceParameters->getOurPercent(),
                $priceParameters->getFee()
            );
        }

        return $price;
    }
}
