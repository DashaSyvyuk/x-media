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

class GenerateHotlineXmlService
{
    use PriceTrait;

    private const FOLDER = 'hotline';
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
        $categories = $this->categoryRepository->getCategoriesForHotline();
        $products = $this->productRepository->getProductsForHotline();
        $feed = $this->feedRepository->findOneBy(['type' => Feed::FEED_HOTLINE]);
        $ignoredBrands = $this->getIgnoredBrandsLookup($feed);
        $priceParametersByCategoryId = $feed
            ? $this->categoryFeedPriceRepository->findByFeedIndexedByCategoryId($feed)
            : [];

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
                    ->startLoop(
                        'items',
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
                                        fn ($item) => in_array($item->getFilter()->getTitle(), ['Марка', 'Виробник'])
                                    )
                                );

                                if (!empty($vendor)) {
                                    $vendorValue = $vendor[0]->getFilterAttribute()->getValue();
                                    if (isset($ignoredBrands[$vendorValue])) {
                                        continue;
                                    }

                                    $categoryId = $product->getCategory()->getId();
                                    $images = $product->getImages();
                                    $characteristics = $product->getCharacteristics();
                                    $warranty = array_values(
                                        array_filter(
                                            $characteristics->toArray(),
                                            fn ($item) => $item->getTitle() == 'Гарантія'
                                        )
                                    );
                                    $priceParameters = $priceParametersByCategoryId[$categoryId] ?? null;

                                    $XMLArray->start('item')
                                        ->add('id', $product->getId())
                                        ->add('categoryId', $categoryId)
                                        ->add('vendor', $vendorValue)
                                        ->add('name', strip_tags(addslashes($product->getTitle())))
                                        ->add('description', htmlentities($product->getDescription(), ENT_XML1))
                                        ->add('url', sprintf('https://x-media.com.ua/products/%s', $product->getId()))
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
                                        ->add('priceRUAH', $this->getPrice($product, $feed, $priceParameters))
                                        ->add('stock', 'В наявності')
                                        ->add('guarantee', $warranty ? (int) $warranty[0]->getValue() : 12, [
                                            'type' => 'manufacturer'
                                        ])
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
                                        ->add('condition', 0)
                                        ->add('code', $product->getProductCode())
                                    ;
                                }
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
