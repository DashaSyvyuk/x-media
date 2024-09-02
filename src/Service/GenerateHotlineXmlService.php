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
        ini_set('memory_limit', '256M');
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
                                ->add('id', $category->getId())
                                ->add('name', $category->getHotlineCategory())
                            ->end();
                        }
                    })
                    ->end()
                    ->startLoop('items', [], function (XMLArray $XMLArray) use ($products) {
                        foreach ($products as $product) {
                            $vendor = array_filter($product->getFilterAttributes()->toArray(), fn ($item) => in_array($item->getFilter()->getTitle(), ['Марка', 'Виробник']));

                            if (!empty($vendor)) {
                                $images = $product->getImages();
                                $characteristics = $product->getCharacteristics();
                                $warranty = array_values(array_filter($product->getCharacteristics()->toArray(), fn ($item) => $item->getTitle() == 'Гарантія'));

                                $XMLArray->start('item')
                                    ->add('id', $product->getId())
                                    ->add('categoryId', $product->getCategory()->getId())
                                    ->add('vendor', $vendor[0]->getFilterAttribute()->getValue())
                                    ->add('name', strip_tags(addslashes($product->getTitle())))
                                    ->add('description', htmlentities($product->getDescription(), ENT_XML1))
                                    ->add('url', sprintf('https://x-media.com.ua/products/%s', $product->getId()))
                                    ->loop(function (XMLArray $XMLArray) use ($images) {
                                        foreach ($images as $image) {
                                            $XMLArray->add('image', 'https://x-media.com.ua/images/products/' . $image->getImageUrl());
                                        }
                                    })
                                    ->add('priceRUAH', $product->getPrice())
                                    ->add('stock', 'В наявності')
                                    ->add('guarantee', $warranty ? (int) $warranty[0]->getValue() : 12, [
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
                                    ->add('code', $product->getProductCode())
                                ;
                            }
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
