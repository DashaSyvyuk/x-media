<?php

namespace App\DataFixtures;

use App\Entity\DeliveryType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DeliveryTypeFixtures extends Fixture
{
    public const DELIVERY_NOVA_POSHTA = 'delivery-nova-poshta';
    public const DELIVERY_PICKUP = 'delivery-pickup';

    public function load(ObjectManager $manager): void
    {
        $novaPoshta = new DeliveryType();
        $novaPoshta->setTitle('Нова Пошта');
        $manager->persist($novaPoshta);
        $this->addReference(self::DELIVERY_NOVA_POSHTA, $novaPoshta);

        $pickup = new DeliveryType();
        $pickup->setTitle('Самовивіз');
        $manager->persist($pickup);
        $this->addReference(self::DELIVERY_PICKUP, $pickup);

        $manager->flush();
    }
}
