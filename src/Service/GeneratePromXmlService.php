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

        $categories = $this->categoryRepository->getCategoriesForProm();
        $products = $this->productRepository->getProductsForProm();
        $feed = $this->feedRepository->findOneBy(['type' => Feed::FEED_PROM]);

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
                    ->startLoop('offers', [], function (XMLArray $XMLArray) use ($products, $feed) {
                        foreach ($products as $product) {
                            $productItem = $this->productRepository->findOneBy(['id' => $product['id']]);
                            $images = $product['images'];
                            $characteristics = $product['characteristics'];
                            $vendor = array_filter($productItem->getFilterAttributes()->toArray(), fn ($item) => in_array($item->getFilter()->getTitle(), ['Марка', 'Виробник']));
                            $priceParameters = $feed ? $this->categoryFeedPriceRepository->findOneBy(['feed' => $feed, 'category' => $productItem->getCategory()]) : null;

                            if (!empty($vendor) && $feed && in_array($vendor[0]->getFilterAttribute()->getValue(), explode(';', $feed->getIgnoreBrands()))) {
                                continue;
                            }

                            $XMLArray->start('offer', [
                                'id' => $product['id'],
                                'selling_type' => 'r',
                                'available' => true
                            ])
                                ->add('name', $product['title'])
                                ->add('name_ua', $product['title'])
                                ->add('categoryId', $product['categoryId'])
                                ->add('portal_category_url', $product['promCategoryLink'])
                                ->add('price', $this->getPrice($productItem, $feed, $priceParameters))
                                ->add('quantity_in_stock', 10)
                                ->add('currencyId', 'UAH')
                                ->loop(function (XMLArray $XMLArray) use ($images) {
                                    foreach ($images as $key => $image) {
                                        if ($key < 10) {
                                            $XMLArray->add('picture', $image);
                                        }
                                    }
                                })
                                ->add('vendor', substr($product['vendor'], 0, 25))
                                ->loop(function (XMLArray $XMLArray) use ($characteristics, $feed) {
                                    foreach ($characteristics as $characteristic) {
                                        $XMLArray->add('param', $this->formatString($characteristic->getValue(), $feed), [
                                            'name' => $this->formatString($characteristic->getTitle(), $feed)
                                        ]);
                                    }
                                })
                                ->add('description', $product['description'])
                                ->add('description_ua', $product['description'])
                                ->add('article', $product['article'])
                            ;
                        }
                    })
                ->end()
                ->end();

            file_put_contents(__DIR__ . '/../../public/prom/products.xml', $this->xmlBuilder->getXML());
        } catch (XMLArrayException|XMLBuilderException $e) {
            var_dump('An exception occurred: ' . $e->getMessage());
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
}
