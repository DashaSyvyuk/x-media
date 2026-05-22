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
use Carbon\Carbon;

class GenerateEkatalogXmlService
{
    use PriceTrait;

    private const FOLDER = 'e-katalog';
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
        $categories = $this->categoryRepository->getCategoriesForEkatalog();
        $products = $this->productRepository->getProductsForEkatalog();
        $feed = $this->feedRepository->findOneBy(['type' => Feed::FEED_EKATALOG]);
        $ignoredBrands = $this->getIgnoredBrandsLookup($feed);
        $priceParametersByCategoryId = $feed
            ? $this->categoryFeedPriceRepository->findByFeedIndexedByCategoryId($feed)
            : [];
        $currencies = [
            [
                'code' => 'UAH',
                'rate' => '1',
            ]
        ];

        try {
            $this->xmlBuilder
                ->createXMLArray()
                ->start('yml_catalog', [
                    'date' => Carbon::now()->format('Y-m-d H:i:s')
                ])
                    ->start('shop')
                        ->add('name', 'X-media')
                        ->add('url', 'https://x-media.com.ua/')
                        ->startLoop('currencies', [], function (XMLArray $XMLArray) use ($currencies) {
                            foreach ($currencies as $currency) {
                                $XMLArray
                                    ->add('currency', null, [
                                        'id' => $currency['code'],
                                        'rate' => $currency['rate']
                                    ]);
                            }
                        })
                        ->end()
                        ->startLoop('categories', [], function (XMLArray $XMLArray) use ($categories) {
                            foreach ($categories as $category) {
                                $XMLArray
                                    ->start('category')
                                        ->add('id', $category->getId())
                                        ->add('name', $category->getTitle())
                                    ->end();
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
                                    $vendor = array_values(
                                        array_filter(
                                            $product->getFilterAttributes()->toArray(),
                                            fn ($item) => in_array(
                                                $item->getFilter()->getTitle(),
                                                ['Марка', 'Виробник']
                                            )
                                        )
                                    );

                                    if (!empty($vendor)) {
                                        $vendorValue = $vendor[0]->getFilterAttribute()->getValue();
                                        if (isset($ignoredBrands[$vendorValue])) {
                                            continue;
                                        }

                                        $categoryId = $product->getCategory()->getId();
                                        $images = $product->getImages();
                                        $priceParameters = $priceParametersByCategoryId[$categoryId] ?? null;

                                        $XMLArray->start('offer', [
                                        'id' => $product->getId(),
                                        'available' => true
                                        ])
                                            ->add(
                                                'url',
                                                sprintf('https://x-media.com.ua/products/%s', $product->getId())
                                            )
                                            ->add('price', $this->getPrice($product, $feed, $priceParameters))
                                            ->add('currencyId', 'UAH')
                                            ->add('categoryId', $categoryId)
                                            ->add('vendor', $vendorValue)
                                            ->add('name', $this->formatString($product->getTitle(), $feed))
                                            ->add(
                                                'description',
                                                htmlspecialchars(strip_tags($product->getDescription()))
                                            )
                                            ->loop(function (XMLArray $XMLArray) use ($images) {
                                                foreach ($images as $image) {
                                                    $XMLArray->add(
                                                        'image',
                                                        sprintf(
                                                            'https://x-media.com.ua/images/products/%s',
                                                            $image->getImageUrl()
                                                        )
                                                    );
                                                }
                                            })
                                            ->add('manufacturer_warranty', true)
                                        ;
                                    }
                                }
                            }
                        )
                    ->end()
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
        $text = htmlspecialchars(strip_tags(addslashes($text)));

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
