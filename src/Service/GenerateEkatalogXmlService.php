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

    private XMLWriterService $xmlWriterService;
    private XMLBuilder $xmlBuilder;

    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository  $productRepository,
        private readonly FeedRepository $feedRepository,
        private readonly CategoryFeedPriceRepository $categoryFeedPriceRepository,
    )
    {
        $this->xmlWriterService = new XMLWriterService();
        $this->xmlBuilder = new XMLBuilder($this->xmlWriterService);
    }

    public function execute(): void
    {
        ini_set('memory_limit', '256M');
        $categories = $this->categoryRepository->getCategoriesForEkatalog();
        $products = $this->productRepository->getProductsForEkatalog();
        $feed = $this->feedRepository->findOneBy(['type' => Feed::FEED_EKATALOG]);
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
                        ->startLoop('offers', [], function (XMLArray $XMLArray) use ($products, $feed) {
                            foreach ($products as $product) {
                                $vendor = array_filter($product->getFilterAttributes()->toArray(), fn ($item) => in_array($item->getFilter()->getTitle(), ['Марка', 'Виробник']));

                                if (!empty($vendor)) {
                                    if ($feed && in_array($vendor[0]->getFilterAttribute()->getValue(), explode(';', $feed->getIgnoreBrands()))) {
                                        continue;
                                    }
                                    $images = $product->getImages();
                                    $priceParameters = $feed ? $this->categoryFeedPriceRepository->findOneBy(['feed' => $feed, 'category' => $product->getCategory()]) : null;

                                    $XMLArray->start('offer', [
                                        'id' => $product->getId(),
                                        'available' => true
                                    ])
                                        ->add('url', sprintf('https://x-media.com.ua/products/%s', $product->getId()))
                                        ->add('price', $this->getPrice($product, $feed, $priceParameters))
                                        ->add('currencyId', 'UAH')
                                        ->add('categoryId', $product->getCategory()->getId())
                                        ->add('vendor', $vendor[0]->getFilterAttribute()->getValue())
                                        ->add('name', $this->formatString($product->getTitle(), $feed))
                                        ->add('description', htmlspecialchars(strip_tags($product->getDescription())))
                                        ->loop(function (XMLArray $XMLArray) use ($images) {
                                            foreach ($images as $image) {
                                                $XMLArray->add('image', 'https://x-media.com.ua/images/products/' . $image->getImageUrl());
                                            }
                                        })
                                        ->add('manufacturer_warranty', true)
                                    ;
                                }
                            }
                        })
                    ->end()
                ->end()
                ->end();

            file_put_contents(__DIR__ . '/../../public/e-katalog/products.xml', $this->xmlBuilder->getXML());
        } catch (XMLArrayException|XMLBuilderException $e) {
            var_dump('An exception occurred: ' . $e->getMessage());
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
}
