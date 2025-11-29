<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    public const PRODUCT_MACBOOK_PRO = 'product-macbook-pro';
    public const PRODUCT_LENOVO_THINKPAD = 'product-lenovo-thinkpad';
    public const PRODUCT_DELL_XPS = 'product-dell-xps';
    public const PRODUCT_IPHONE = 'product-iphone';
    public const PRODUCT_SAMSUNG_GALAXY = 'product-samsung-galaxy';
    public const PRODUCT_IPAD_PRO = 'product-ipad-pro';
    public const PRODUCT_SAMSUNG_TAB = 'product-samsung-tab';

    public function load(ObjectManager $manager): void
    {
        // Notebooks
        $macbook = new Product();
        $macbook->setTitle('Apple MacBook Pro 16');
        $macbook->setProductCode('MBP-16-001');
        $macbook->setPrice(89999);
        $macbook->setDescription('Потужний ноутбук для професіоналів');
        $macbook->setStatus(Product::STATUS_ACTIVE);
        $macbook->setAvailability(Product::AVAILABILITY_AVAILABLE);
        $macbook->setCategory($this->getReference(CategoryFixtures::CATEGORY_NOTEBOOKS, Category::class));
        $macbook->setMetaKeyword('');
        $macbook->setMetaDescription('');
        $manager->persist($macbook);
        $this->addReference(self::PRODUCT_MACBOOK_PRO, $macbook);

        $thinkpad = new Product();
        $thinkpad->setTitle('Lenovo ThinkPad X1');
        $thinkpad->setProductCode('LEN-X1-002');
        $thinkpad->setPrice(45999);
        $thinkpad->setDescription('Бізнес ноутбук преміум класу');
        $thinkpad->setStatus(Product::STATUS_ACTIVE);
        $thinkpad->setAvailability(Product::AVAILABILITY_AVAILABLE);
        $thinkpad->setCategory($this->getReference(CategoryFixtures::CATEGORY_NOTEBOOKS, Category::class));
        $thinkpad->setMetaKeyword('');
        $thinkpad->setMetaDescription('');
        $manager->persist($thinkpad);
        $this->addReference(self::PRODUCT_LENOVO_THINKPAD, $thinkpad);

        $dellXps = new Product();
        $dellXps->setTitle('Dell XPS 15');
        $dellXps->setProductCode('DELL-XPS-003');
        $dellXps->setPrice(55999);
        $dellXps->setDescription('Елегантний та продуктивний ноутбук');
        $dellXps->setStatus(Product::STATUS_ACTIVE);
        $dellXps->setAvailability(Product::AVAILABILITY_AVAILABLE);
        $dellXps->setCategory($this->getReference(CategoryFixtures::CATEGORY_NOTEBOOKS, Category::class));
        $dellXps->setMetaKeyword('');
        $dellXps->setMetaDescription('');
        $manager->persist($dellXps);
        $this->addReference(self::PRODUCT_DELL_XPS, $dellXps);

        // Smartphones
        $iphone = new Product();
        $iphone->setTitle('iPhone 15 Pro');
        $iphone->setProductCode('IPH-15P-004');
        $iphone->setPrice(42999);
        $iphone->setDescription('Новітній флагман від Apple');
        $iphone->setStatus(Product::STATUS_ACTIVE);
        $iphone->setAvailability(Product::AVAILABILITY_AVAILABLE);
        $iphone->setCategory($this->getReference(CategoryFixtures::CATEGORY_SMARTPHONES, Category::class));
        $iphone->setMetaKeyword('');
        $iphone->setMetaDescription('');
        $manager->persist($iphone);
        $this->addReference(self::PRODUCT_IPHONE, $iphone);

        $samsungPhone = new Product();
        $samsungPhone->setTitle('Samsung Galaxy S24');
        $samsungPhone->setProductCode('SAM-S24-005');
        $samsungPhone->setPrice(35999);
        $samsungPhone->setDescription('Потужний Android смартфон');
        $samsungPhone->setStatus(Product::STATUS_ACTIVE);
        $samsungPhone->setAvailability(Product::AVAILABILITY_AVAILABLE);
        $samsungPhone->setCategory($this->getReference(CategoryFixtures::CATEGORY_SMARTPHONES, Category::class));
        $samsungPhone->setMetaKeyword('');
        $samsungPhone->setMetaDescription('');
        $manager->persist($samsungPhone);
        $this->addReference(self::PRODUCT_SAMSUNG_GALAXY, $samsungPhone);

        // Tablets
        $ipadPro = new Product();
        $ipadPro->setTitle('iPad Pro 12.9');
        $ipadPro->setProductCode('IPAD-PRO-006');
        $ipadPro->setPrice(48999);
        $ipadPro->setDescription('Професійний планшет з M2');
        $ipadPro->setStatus(Product::STATUS_ACTIVE);
        $ipadPro->setAvailability(Product::AVAILABILITY_AVAILABLE);
        $ipadPro->setCategory($this->getReference(CategoryFixtures::CATEGORY_TABLETS, Category::class));
        $ipadPro->setMetaKeyword('');
        $ipadPro->setMetaDescription('');
        $manager->persist($ipadPro);
        $this->addReference(self::PRODUCT_IPAD_PRO, $ipadPro);

        $samsungTab = new Product();
        $samsungTab->setTitle('Samsung Galaxy Tab S9');
        $samsungTab->setProductCode('TAB-S9-007');
        $samsungTab->setPrice(29999);
        $samsungTab->setDescription('Android планшет з AMOLED екраном');
        $samsungTab->setStatus(Product::STATUS_ACTIVE);
        $samsungTab->setAvailability(Product::AVAILABILITY_AVAILABLE);
        $samsungTab->setCategory($this->getReference(CategoryFixtures::CATEGORY_TABLETS, Category::class));
        $samsungTab->setMetaKeyword('');
        $samsungTab->setMetaDescription('');
        $manager->persist($samsungTab);
        $this->addReference(self::PRODUCT_SAMSUNG_TAB, $samsungTab);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
        ];
    }
}
