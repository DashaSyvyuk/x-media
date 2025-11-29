<?php

namespace App\DataFixtures;

use App\Entity\Setting;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SettingFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
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

        $manager->flush();
    }
}
