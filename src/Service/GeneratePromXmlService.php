<?php

namespace App\Service;

use AaronDDM\XMLBuilder\Exception\XMLBuilderException;
use AaronDDM\XMLBuilder\XMLArray;
use AaronDDM\XMLBuilder\XMLBuilder;
use AaronDDM\XMLBuilder\Writer\XMLWriterService;
use AaronDDM\XMLBuilder\Exception\XMLArrayException;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;

class GeneratePromXmlService
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

        $categories = $this->categoryRepository->getCategoriesForProm();
        $products = $this->productRepository->getProductsForProm();

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
                    ->startLoop('offers', [], function (XMLArray $XMLArray) use ($products) {
                        foreach ($products as $product) {
                            $images = $product['images'];
                            $characteristics = $product['characteristics'];

                            $XMLArray->start('offer', [
                                'id' => $product['id'],
                                'selling_type' => 'r',
                                'available' => true
                            ])
                                ->add('name', $product['title'])
                                ->add('name_ua', $product['title'])
                                ->add('categoryId', $product['categoryId'])
                                ->add('portal_category_url', $product['promCategoryLink'])
                                ->add('price', ceil($product['price'] * 1.04 / 100) * 100)
                                ->add('quantity_in_stock', 10)
                                ->add('currencyId', 'UAH')
                                ->loop(function (XMLArray $XMLArray) use ($images) {
                                    foreach ($images as $image) {
                                        $XMLArray->add('picture', $image);
                                    }
                                })
                                ->add('vendor', $product['vendor'])
                                ->loop(function (XMLArray $XMLArray) use ($characteristics) {
                                    foreach ($characteristics as $characteristic) {
                                        $XMLArray->add('param', htmlspecialchars(addslashes($characteristic->getValue())), [
                                            'name' => htmlspecialchars(addslashes($characteristic->getTitle()))
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
}
