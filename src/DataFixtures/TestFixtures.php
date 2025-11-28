<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Setting;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TestFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create required settings first
        $settings = [
            ['title' => 'Phone Number', 'slug' => 'phone_number', 'value' => '+380123456789'],
            ['title' => 'Email', 'slug' => 'email', 'value' => 'test@example.com'],
            ['title' => 'Shop Name', 'slug' => 'shop_name', 'value' => 'Test Shop'],
            ['title' => 'Shop Site', 'slug' => 'shop_site', 'value' => 'http://test.local'],
            ['title' => 'Shop Address', 'slug' => 'shop_address', 'value' => 'Test Address 123'],
            ['title' => 'Pickup Point Address', 'slug' => 'pick_up_point_address', 'value' => 'Pickup Point Address'],
            ['title' => 'Pagination Limit', 'slug' => 'pagination_limit', 'value' => '20'],
        ];

        foreach ($settings as $settingData) {
            $setting = new Setting();
            $setting->setTitle($settingData['title']);
            $setting->setSlug($settingData['slug']);
            $setting->setValue($settingData['value']);
            $manager->persist($setting);
        }

        // Create categories
        $categories = [];

        $category1 = new Category();
        $category1->setTitle('Ноутбуки');
        $category1->setSlug('notebooks');
        $category1->setStatus('ACTIVE');
        $manager->persist($category1);
        $categories[] = $category1;

        $category2 = new Category();
        $category2->setTitle('Смартфони');
        $category2->setSlug('smartphones');
        $category2->setStatus('ACTIVE');
        $manager->persist($category2);
        $categories[] = $category2;

        $category3 = new Category();
        $category3->setTitle('Планшети');
        $category3->setSlug('tablets');
        $category3->setStatus('ACTIVE');
        $manager->persist($category3);
        $categories[] = $category3;

        // Create products
        $products = [
            [
                'title' => 'Apple MacBook Pro 16',
                'code' => 'MBP-16-001',
                'price' => 89999,
                'description' => 'Потужний ноутбук для професіоналів',
                'category' => $category1,
            ],
            [
                'title' => 'Lenovo ThinkPad X1',
                'code' => 'LEN-X1-002',
                'price' => 45999,
                'description' => 'Бізнес ноутбук преміум класу',
                'category' => $category1,
            ],
            [
                'title' => 'Dell XPS 15',
                'code' => 'DELL-XPS-003',
                'price' => 55999,
                'description' => 'Елегантний та продуктивний ноутбук',
                'category' => $category1,
            ],
            [
                'title' => 'iPhone 15 Pro',
                'code' => 'IPH-15P-004',
                'price' => 42999,
                'description' => 'Новітній флагман від Apple',
                'category' => $category2,
            ],
            [
                'title' => 'Samsung Galaxy S24',
                'code' => 'SAM-S24-005',
                'price' => 35999,
                'description' => 'Потужний Android смартфон',
                'category' => $category2,
            ],
            [
                'title' => 'iPad Pro 12.9',
                'code' => 'IPAD-PRO-006',
                'price' => 48999,
                'description' => 'Професійний планшет з M2',
                'category' => $category3,
            ],
            [
                'title' => 'Samsung Galaxy Tab S9',
                'code' => 'TAB-S9-007',
                'price' => 29999,
                'description' => 'Android планшет з AMOLED екраном',
                'category' => $category3,
            ],
        ];

        foreach ($products as $productData) {
            $product = new Product();
            $product->setTitle($productData['title']);
            $product->setProductCode($productData['code']);
            $product->setPrice($productData['price']);
            $product->setDescription($productData['description']);
            $product->setStatus(Product::STATUS_ACTIVE);
            $product->setAvailability(Product::AVAILABILITY_AVAILABLE);
            $product->setCategory($productData['category']);
            $product->setMetaKeyword('');
            $product->setMetaDescription('');

            $manager->persist($product);
        }

        $manager->flush();
    }
}
