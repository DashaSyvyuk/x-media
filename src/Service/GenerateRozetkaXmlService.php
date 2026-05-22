<?php

namespace App\Service;

use AaronDDM\XMLBuilder\Exception\XMLBuilderException;
use AaronDDM\XMLBuilder\XMLArray;
use AaronDDM\XMLBuilder\XMLBuilder;
use AaronDDM\XMLBuilder\Writer\XMLWriterService;
use AaronDDM\XMLBuilder\Exception\XMLArrayException;
use App\Entity\Feed;
use App\Entity\ProductRozetkaCharacteristicValue;
use App\Entity\RozetkaProduct;
use App\Repository\CategoryFeedPriceRepository;
use App\Repository\CategoryRepository;
use App\Repository\FeedRepository;
use App\Repository\ProductRepository;
use Carbon\Carbon;

class GenerateRozetkaXmlService
{
    use PriceTrait;

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

    public function execute(string $activeFor = 'active_for_a'): ?string
    {
        $activeForInCamelCase = lcfirst(str_replace('_', '', ucwords($activeFor, '_')));
        $categories = $this->categoryRepository->getCategoriesForRozetka($activeForInCamelCase);
        $products = $this->productRepository->getProductsForRozetka($activeForInCamelCase);
        $feed = $this->feedRepository->findOneBy(['type' => Feed::FEED_ROZETKA]);
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
                                    /** @var RozetkaProduct $rozetkaProduct */
                                    $rozetkaProduct = $product->getRozetka();
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
                                        $characteristics = $rozetkaProduct->getValues();
                                        $priceParameters = $priceParametersByCategoryId[$categoryId] ?? null;
                                        $promoPrice = $rozetkaProduct->getPromoPrice();
                                        $hasPromo   = $rozetkaProduct->getPromoPriceActive() && $promoPrice !== null;

                                        $XMLArray->start('offer', [
                                        'id' => $product->getId(),
                                        'available' => 'true',
                                        ])
                                            ->add('stock_quantity', $rozetkaProduct->getStockQuantity() ?: 3)
                                            ->add(
                                                'url',
                                                sprintf('https://x-media.com.ua/products/%s', $product->getId())
                                            )
                                            ->add('price', $rozetkaProduct->getPrice() ?: $this->getPrice(
                                                $product,
                                                $feed,
                                                $priceParameters
                                            ))
                                            ->add('old_price', $rozetkaProduct->getCrossedOutPrice())
                                            ->loop(function (XMLArray $XMLArray) use ($hasPromo, $promoPrice) {
                                                if ($hasPromo) {
                                                    $XMLArray->add(
                                                        'price_promo',
                                                        number_format($promoPrice, 0, '.', '')
                                                    );
                                                }
                                            })
                                            ->add('currencyId', 'UAH')
                                            ->add('categoryId', $categoryId)
                                            ->loop(function (XMLArray $XMLArray) use ($images) {
                                                foreach ($images as $image) {
                                                    $XMLArray->add(
                                                        'picture',
                                                        sprintf(
                                                            'https://x-media.com.ua/images/products/%s',
                                                            $image->getImageUrl()
                                                        )
                                                    );
                                                }
                                            })
                                            ->add('vendor', $vendorValue)
                                            ->add(
                                                'name',
                                                sprintf(
                                                    '%s (%s)',
                                                    strip_tags(addslashes($rozetkaProduct->getTitle())),
                                                    $product->getProductCode()
                                                )
                                            )
                                            ->add('description', $this->formatString($rozetkaProduct->getDescription()))
                                            ->add('article', $product->getId())
                                            ->add('series', $rozetkaProduct->getSeries())
                                            ->loop(function (XMLArray $XMLArray) use ($characteristics, $feed) {
                                                /** @var ProductRozetkaCharacteristicValue $characteristic */
                                                foreach ($characteristics as $characteristic) {
                                                    $values = $this->getCharacteristicValue($characteristic);

                                                    if (is_array($values)) {
                                                        $XMLArray->startLoop('param', [
                                                        'name' => $this->convertString(
                                                            $characteristic->getCharacteristic()?->getTitle() ?? '',
                                                            $feed
                                                        )
                                                        ], function (XMLArray $XMLArray) use ($values) {
                                                            foreach ($values as $value) {
                                                                $XMLArray->add('value', htmlspecialchars($value));
                                                            }
                                                        })
                                                        ->end();
                                                    } else {
                                                        $XMLArray->add('param', $this->convertString($values, $feed), [
                                                        'name' => $this->convertString(
                                                            $characteristic->getCharacteristic()?->getTitle() ?? '',
                                                            $feed
                                                        )
                                                        ]);
                                                    }
                                                }
                                            })
                                            ->end();
                                    }
                                }
                            }
                        )
                        ->end()
                    ->end()
                ->end();

            $folder    = sprintf('rozetka_for_%s', substr($activeFor, -1));
            $localPath = __DIR__ . '/../../public/' . $folder . '/' . self::FILE_NAME;
            $localDir  = dirname($localPath);

            if (! is_dir($localDir)) {
                @mkdir($localDir, 0775, true);
            }

            file_put_contents($localPath, $this->xmlBuilder->getXML());

            return $this->bunny->uploadAndGetUrl($localPath, $folder, self::FILE_NAME);
        } catch (XMLArrayException | XMLBuilderException $e) {
            var_dump('An exception occurred: ' . $e->getMessage());

            return null;
        }
    }

    private function formatString(string $string): string
    {
        return sprintf('<![CDATA[%s]]>', trim(strip_tags($string)));
    }

    private function convertString(string $text, ?Feed $feed): string
    {
        $text = strip_tags($text);

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

    /**
     * @param ProductRozetkaCharacteristicValue $value
     *
     * @return string|array<string>
     */
    private function getCharacteristicValue(ProductRozetkaCharacteristicValue $value): string|array
    {
        $characteristic = $value->getCharacteristic();
        $type = $characteristic?->getType() ?? '';

        return match ($type) {
            'ListValues'          => $value->getValues()->map(fn ($value) => $value->getTitle())->toArray(),
            'CheckBoxGroupValues' => $value->getValues()->map(fn ($value) => $value->getTitle())->toArray(),
            'List'                => implode(
                ',',
                $value->getValues()->map(fn ($value) => $value->getTitle())->toArray()
            ),
            'ComboBox'            => $value->getValue() ? $value->getValue()->getTitle() : '',
            'Integer', 'Decimal', 'TextInput', 'TextArea' => $value->getStringValue(),
            default               => '',
        };
    }
}
