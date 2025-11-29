<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public const CATEGORY_NOTEBOOKS = 'category-notebooks';
    public const CATEGORY_SMARTPHONES = 'category-smartphones';
    public const CATEGORY_TABLETS = 'category-tablets';

    public function load(ObjectManager $manager): void
    {
        $notebooks = new Category();
        $notebooks->setTitle('Ноутбуки');
        $notebooks->setSlug('notebooks');
        $notebooks->setStatus('ACTIVE');
        $manager->persist($notebooks);
        $this->addReference(self::CATEGORY_NOTEBOOKS, $notebooks);

        $smartphones = new Category();
        $smartphones->setTitle('Смартфони');
        $smartphones->setSlug('smartphones');
        $smartphones->setStatus('ACTIVE');
        $manager->persist($smartphones);
        $this->addReference(self::CATEGORY_SMARTPHONES, $smartphones);

        $tablets = new Category();
        $tablets->setTitle('Планшети');
        $tablets->setSlug('tablets');
        $tablets->setStatus('ACTIVE');
        $manager->persist($tablets);
        $this->addReference(self::CATEGORY_TABLETS, $tablets);

        $manager->flush();
    }
}
