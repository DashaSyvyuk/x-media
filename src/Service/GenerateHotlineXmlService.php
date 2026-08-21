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

    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository,
        private readonly FeedRepository $feedRepository,
        private readonly CategoryFeedPriceRepository $categoryFeedPriceRepository,
        private readonly BunnyStorageClient $bunny,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly string $cdnUrl,
    ) {
    }

    public function execute(): ?string
    {
        $this->allowLongRunningProcess();

        $categories = $this->categoryRepository->getCategoriesForHotline();
        $products = $this->productRepository->getProductsForHotline();
        $feed = $this->feedRepository->findOneBy(['type' => Feed::FEED_HOTLINE]);
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

            $writer->startElement('items');
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
                $writer->writeElement('name', $this->formatString($product->getTitle(), $feed));
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
                    $writer->writeAttribute('name', $this->formatString($characteristic->getTitle(), $feed));
                    $writer->text($this->formatString($characteristic->getValue(), $feed));
                    $writer->endElement();
                }

                $writer->writeElement('condition', '0');
                $writer->writeElement('code', (string) $product->getProductCode());
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
            $this->logger->error('Hotline XML generation failed.', ['exception' => $e]);

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
