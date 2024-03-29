<?php

namespace App\Service;

use AaronDDM\XMLBuilder\Exception\XMLBuilderException;
use AaronDDM\XMLBuilder\XMLArray;
use AaronDDM\XMLBuilder\XMLBuilder;
use AaronDDM\XMLBuilder\Writer\XMLWriterService;
use AaronDDM\XMLBuilder\Exception\XMLArrayException;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;

class GenerateHotlineXmlService
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
        $categories = $this->categoryRepository->getCategoriesForHotline();
        $products = $this->productRepository->getProductsForHotline();

        try {
            $this->xmlBuilder
                ->createXMLArray()
                ->start('price')
                    ->add('date', date('Y-m-d H:i:s'))
                    ->add('firmName', 'X-media')
                    ->add('firmId', 41387)
                    ->add('delivery', null, [
                            'delivery_id' => 1,
                            'type'        => 'warehouse',
                            'carrier'     => 'NP'
                        ])
                    ->startLoop('categories', [], function (XMLArray $XMLArray) use ($categories) {
                        foreach ($categories as $category) {
                            $XMLArray->start('category')
                                ->add('id', $category['id'])
                                ->add('name', $category['title'])
                            ->end();
                        }
                    })
                    ->end()
                    ->startLoop('items', [], function (XMLArray $XMLArray) use ($products) {
                        foreach ($products as $product) {
                            $images = $product['images'];
                            $characteristics = $product['characteristics'];

                            $XMLArray->start('item')
                                ->add('id', $product['id'])
                                ->add('categoryId', $product['categoryId'])
                                ->add('vendor', $product['vendor'])
                                ->add('name', $product['title'])
                                ->add('description', $product['description'])
                                ->add('url', sprintf('https://x-media.com.ua/products/%s', $product['id']))
                                ->loop(function (XMLArray $XMLArray) use ($images) {
                                    foreach ($images as $image) {
                                        $XMLArray->add('image', $image);
                                    }
                                })
                                ->add('priceRUAH', $product['price'])
                                ->add('stock', 'В наявності')
                                ->add('guarantee', $product['warranty'], [
                                    'type' => 'manufacturer'
                                ])
                                ->loop(function (XMLArray $XMLArray) use ($characteristics) {
                                    foreach ($characteristics as $characteristic) {
                                        $XMLArray->add('param', htmlspecialchars(addslashes($characteristic->getValue())), [
                                            'name' => htmlspecialchars(addslashes($characteristic->getTitle()))
                                        ]);
                                    }
                                })
                                ->add('condition', 0)
                                ->add('code', $product['article'])
                            ;
                        }
                    })
                    ->end()
                ->end();

            file_put_contents(__DIR__ . '/../../public/hotline/products.xml', $this->xmlBuilder->getXML());
        } catch (XMLArrayException|XMLBuilderException $e) {
            var_dump('An exception occurred: ' . $e->getMessage());
        }
    }
}
