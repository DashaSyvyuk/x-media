<?php

namespace App\DataFixtures;

use App\Entity\PaymentType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PaymentTypeFixtures extends Fixture
{
    public const PAYMENT_CASH = 'payment-cash';
    public const PAYMENT_CARD = 'payment-card';

    public function load(ObjectManager $manager): void
    {
        $cash = new PaymentType();
        $cash->setTitle('Готівка');
        $manager->persist($cash);
        $this->addReference(self::PAYMENT_CASH, $cash);

        $card = new PaymentType();
        $card->setTitle('Карткою при отриманні');
        $manager->persist($card);
        $this->addReference(self::PAYMENT_CARD, $card);

        $manager->flush();
    }
}
