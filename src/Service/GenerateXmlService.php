<?php

namespace App\Service;

use AaronDDM\XMLBuilder\Exception\XMLBuilderException;
use AaronDDM\XMLBuilder\XMLArray;
use AaronDDM\XMLBuilder\XMLBuilder;
use AaronDDM\XMLBuilder\Writer\XMLWriterService;
use AaronDDM\XMLBuilder\Exception\XMLArrayException;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;

class GenerateXmlService
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

    public function execute(array $ids): void
    {
        $categories = $this->categoryRepository->getCategoriesForProducts($ids);
        $products = $this->productRepository->getProductsByIds($ids);

        try {
            $this->xmlBuilder
                ->createXMLArray()
                ->start('shop')
                    ->startLoop('categories', [], function (XMLArray $XMLArray) use ($categories) {
                        foreach ($categories as $category) {
                            $XMLArray->add('category', $category['title'], [
                                'id'         => $category['id'],
                                'portal_url' => $category['hotlineLink']
                            ]);
                        }
                    })
                    ->end()
                    ->startLoop('offers', [], function (XMLArray $XMLArray) use ($products) {
                        foreach ($products as $product) {
                            $images = $product['images'];
                            $characteristics = $product['characteristics'];

                            $XMLArray->start('offer', [
                                'id' => $product['id'],
                                'available' => true,
                                'in_stock' => true,
                                'selling_type' => 'r'
                            ])
                                ->add('name', $product['title'])
                                ->add('name_ua', $product['title'])
                                ->add('categoryId', $product['categoryId'])
                                ->add('portal_category_url', $product['categoryHotlineLink'])
                                ->add('price', $product['price'])
                                ->add('quantity_in_stock', 10)
                                ->add('currencyId', 'UAH')
                                ->add('barcode', str_pad($product['id'], 25, 0, STR_PAD_LEFT))
                                ->loop(function (XMLArray $XMLArray) use ($images) {
                                    foreach ($images as $image) {
                                        $XMLArray->add('picture', $image);
                                    }
                                })
                                ->loop(function (XMLArray $XMLArray) use ($characteristics) {
                                    foreach ($characteristics as $characteristic) {
                                        $XMLArray->add('param', strip_tags(addslashes($characteristic->getValue())), [
                                            'name' => strip_tags(addslashes($characteristic->getTitle()))
                                        ]);
                                    }
                                })
                                ->add('description', $product['description'])
                                ->add('description_ua', $product['description'])
                                ->add('keywords', $product['keywords'])
                            ;
                        }
                    })
                    ->end()
                ->end();

            file_put_contents(__DIR__ . '/../../public/xml/products.xml', $this->xmlBuilder->getXML());
        } catch (XMLArrayException|XMLBuilderException $e) {
            var_dump('An exception occurred: ' . $e->getMessage());
        }
    }
}
