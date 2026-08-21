<?php

namespace App\Service;

use App\Entity\Feed;
use App\Repository\CategoryFeedPriceRepository;
use App\Repository\CategoryRepository;
use App\Repository\FeedRepository;
use App\Repository\ProductRepository;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class GenerateEkatalogXmlService
{
    use PriceTrait;

    private const FOLDER = 'e-katalog';
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

        $categories = $this->categoryRepository->getCategoriesForEkatalog();
        $products = $this->productRepository->getProductsForEkatalog();
        $feed = $this->feedRepository->findOneBy(['type' => Feed::FEED_EKATALOG]);
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
            $writer->startElement('yml_catalog');
            $writer->writeAttribute('date', Carbon::now()->format('Y-m-d H:i:s'));
            $writer->startElement('shop');
            $writer->writeElement('name', 'X-media');
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
                $writer->writeElement('id', (string) $category->getId());
                $writer->writeElement('name', (string) $category->getTitle());
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
                $priceParameters = $priceParametersByCategoryId[$categoryId] ?? null;

                $writer->startElement('offer');
                $writer->writeAttribute('id', (string) $product->getId());
                $writer->writeAttribute('available', 'true');
                $writer->writeElement('url', sprintf('https://x-media.com.ua/products/%s', $product->getId()));
                $writer->writeElement('price', (string) $this->getPrice($product, $feed, $priceParameters));
                $writer->writeElement('currencyId', 'UAH');
                $writer->writeElement('categoryId', (string) $categoryId);
                $writer->writeElement('vendor', (string) $vendorValue);
                $writer->writeElement('name', $this->formatString($product->getTitle(), $feed));
                $writer->writeElement('description', strip_tags((string) $product->getDescription()));
                foreach ($images as $image) {
                    $writer->writeElement(
                        'image',
                        rtrim($this->cdnUrl, '/') . '/products/' . $image->getImageUrl()
                    );
                }
                $writer->writeElement('manufacturer_warranty', 'true');
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

            return $this->bunny->uploadAndGetUrl($localPath, self::FOLDER, self::FILE_NAME);
        } catch (\Throwable $e) {
            $this->logger->error('E-Katalog XML generation failed.', ['exception' => $e]);

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
