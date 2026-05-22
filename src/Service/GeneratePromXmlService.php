<?php

namespace App\Service;

use AaronDDM\XMLBuilder\Exception\XMLBuilderException;
use AaronDDM\XMLBuilder\XMLArray;
use AaronDDM\XMLBuilder\XMLBuilder;
use AaronDDM\XMLBuilder\Writer\XMLWriterService;
use AaronDDM\XMLBuilder\Exception\XMLArrayException;
use App\Entity\Feed;
use App\Repository\CategoryFeedPriceRepository;
use App\Repository\CategoryRepository;
use App\Repository\FeedRepository;
use App\Repository\ProductRepository;

class GeneratePromXmlService
{
    use PriceTrait;

    private const FOLDER = 'prom';
    private const FILE_NAME = 'products.xml';

    private XMLWriterService $xmlWriterService;
    private XMLBuilder $xmlBuilder;

    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository,
        private readonly FeedRepository $feedRepository,
        private readonly CategoryFeedPriceRepository $categoryFeedPriceRepository,
        private readonly BunnyStorageClient $bunny,
    ) {
        $this->xmlWriterService = new XMLWriterService();
        $this->xmlBuilder = new XMLBuilder($this->xmlWriterService);
    }

    public function execute(): ?string
    {
        $categories = $this->categoryRepository->getCategoriesForProm();
        $products = $this->productRepository->getProductsForProm();
        $feed = $this->feedRepository->findOneBy(['type' => Feed::FEED_PROM]);
        $ignoredBrands = $this->getIgnoredBrandsLookup($feed);
        $priceParametersByCategoryId = $feed
            ? $this->categoryFeedPriceRepository->findByFeedIndexedByCategoryId($feed)
            : [];

        try {
            $this->xmlBuilder
                ->createXMLArray()
                ->start('shop')
                    ->startLoop('categories', [], function (XMLArray $XMLArray) use ($categories) {
                        foreach ($categories as $category) {
                            $XMLArray->add('category', $category['title'], [
                                'id' => $category['id'],
                                'portal_url' => $category['promCategoryLink']
                            ]);
                        }
                    })
                    ->end()
                    ->startLoop(
                        'offers',
                        [],
                        function (XMLArray $XMLArray) use (
                            $products,
                            $feed,
                            $ignoredBrands,
                            $priceParametersByCategoryId
                        ) {
                            foreach ($products as $product) {
                                $images = $product['images'];
                                $characteristics = $product['characteristics'];
                                $vendor = (string) ($product['vendor'] ?? '');
                                if ($vendor !== '' && isset($ignoredBrands[$vendor])) {
                                    continue;
                                }

                                $priceParameters = $priceParametersByCategoryId[$product['categoryId']] ?? null;

                                $XMLArray->start('offer', [
                                'id' => $product['id'],
                                'selling_type' => 'r',
                                'available' => true
                                ])
                                    ->add('name', sprintf('%s (%s)', $product['title'], $product['productCode']))
                                    ->add('name_ua', sprintf('%s (%s)', $product['title'], $product['productCode']))
                                    ->add('categoryId', $product['categoryId'])
                                    ->add('portal_category_url', $product['promCategoryLink'])
                                    ->add('price', $this->getPriceFromValue((int) $product['price'], $priceParameters))
                                    ->add('quantity_in_stock', 10)
                                    ->add('currencyId', 'UAH')
                                    ->loop(function (XMLArray $XMLArray) use ($images) {
                                        foreach ($images as $key => $image) {
                                            if ($key < 10) {
                                                $XMLArray->add('picture', $image);
                                            }
                                        }
                                    })
                                    ->add('vendor', substr($vendor, 0, 25))
                                    ->loop(function (XMLArray $XMLArray) use ($characteristics, $feed) {
                                        foreach ($characteristics as $characteristic) {
                                            $XMLArray->add(
                                                'param',
                                                $this->formatString($characteristic->getValue(), $feed),
                                                [
                                                'name' => $this->formatString($characteristic->getTitle(), $feed)
                                                ]
                                            );
                                        }
                                    })
                                    ->add('description', $product['description'])
                                    ->add('description_ua', $product['description'])
                                    ->add('article', $product['article'])
                                ;
                            }
                        }
                    )
                ->end()
                ->end();

            $localPath = __DIR__ . '/../../public/' . self::FOLDER . '/' . self::FILE_NAME;
            $localDir  = dirname($localPath);

            if (! is_dir($localDir)) {
                @mkdir($localDir, 0775, true);
            }

            file_put_contents($localPath, $this->xmlBuilder->getXML());

            return $this->bunny->uploadAndGetUrl($localPath, self::FOLDER, self::FILE_NAME);
        } catch (XMLArrayException | XMLBuilderException $e) {
            var_dump('An exception occurred: ' . $e->getMessage());

            return null;
        }
    }

    private function formatString(string $text, ?Feed $feed): string
    {
        $text = strip_tags(addslashes($text));

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
