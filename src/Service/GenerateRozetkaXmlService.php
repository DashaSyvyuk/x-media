<?php

namespace App\Service;

use AaronDDM\XMLBuilder\Exception\XMLBuilderException;
use AaronDDM\XMLBuilder\XMLArray;
use AaronDDM\XMLBuilder\XMLBuilder;
use AaronDDM\XMLBuilder\Writer\XMLWriterService;
use AaronDDM\XMLBuilder\Exception\XMLArrayException;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Carbon\Carbon;

class GenerateRozetkaXmlService
{
    private XMLWriterService $xmlWriterService;
    private XMLBuilder $xmlBuilder;

    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository  $productRepository,
    )
    {
        $this->xmlWriterService = new XMLWriterService();
        $this->xmlBuilder = new XMLBuilder($this->xmlWriterService);
    }

    public function execute(): void
    {
        ini_set('memory_limit', '256M');
        $categories = $this->categoryRepository->getCategoriesForRozetka();
        $products = $this->productRepository->getProductsForRozetka();
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
                    'date' => Carbon::now()->format('Y-m-d H:i')
                ])
                    ->start('shop')
                        ->add('name', 'X-media')
                        ->add('company', 'X-media')
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
                                    ->add('category', $category->getRozetkaCategory(), [
                                        'id' => $category->getId(),
                                    ]);
                            }
                        })
                        ->end()
                        ->startLoop('offers', [], function (XMLArray $XMLArray) use ($products) {
                            foreach ($products as $product) {
                                $vendor = array_filter($product->getFilterAttributes()->toArray(), fn ($item) => in_array($item->getFilter()->getTitle(), ['Марка', 'Виробник']));

                                if (!empty($vendor)) {
                                    $images = $product->getImages();
                                    $characteristics = $product->getCharacteristics();

                                    $XMLArray->start('offer', [
                                        'id' => $product->getId(),
                                        'available' => true,
                                    ])
                                        ->add('stock_quantity', rand(1, 3))
                                        ->add('url', sprintf('https://x-media.com.ua/products/%s', $product->getId()))
                                        ->add('price', $this->adjustPrice($product->getPrice()))
                                        ->add('currencyId', 'UAH')
                                        ->add('categoryId', $product->getCategory()->getId())
                                        ->loop(function (XMLArray $XMLArray) use ($images) {
                                            foreach ($images as $image) {
                                                $XMLArray->add('picture', 'https://x-media.com.ua/images/products/' . $image->getImageUrl());
                                            }
                                        })
                                        ->add('vendor', $vendor[0]->getFilterAttribute()->getValue())
                                        ->add('name', strip_tags(addslashes($product->getTitle())))
                                        ->add('description', htmlentities($product->getDescription(), ENT_XML1))
                                        ->loop(function (XMLArray $XMLArray) use ($characteristics) {
                                            foreach ($characteristics as $characteristic) {
                                                $XMLArray->add('param', htmlspecialchars(addslashes($characteristic->getValue())), [
                                                    'name' => htmlspecialchars(addslashes($characteristic->getTitle()))
                                                ]);
                                            }
                                        })
                                    ;
                                }
                            }
                        })
                        ->end()
                    ->end()
                ->end();

            file_put_contents(__DIR__ . '/../../public/rozetka/products.xml', $this->xmlBuilder->getXML());
        } catch (XMLArrayException|XMLBuilderException $e) {
            var_dump('An exception occurred: ' . $e->getMessage());
        }
    }

    private function adjustPrice(int $price, int $ourPercent = 10, int $fee = 15) {
        $totalDiscount = $ourPercent + $fee; // Сума знижок
        $multiplier = 1 / (1 - $totalDiscount / 100);

        $newPrice = $price * $multiplier;

        if ($price < 20000) {
            // Округлення до сотень і віднімання 1
            $newPrice = ceil($newPrice / 100) * 100 - 1;
        } else {
            $newPrice = ceil($newPrice / 1000) * 1000 - 1;
        }

        return $newPrice;
    }
}
