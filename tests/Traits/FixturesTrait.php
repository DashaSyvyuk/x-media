<?php

namespace App\Tests\Traits;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;

trait FixturesTrait
{
    protected function loadFixtures(): void
    {
        $container = static::getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();

        // Purge the database
        $purger = new ORMPurger($entityManager);
        $purger->purge();

        // Load fixtures
        $fixturesLoader = $container->get('doctrine.fixtures.loader');
        $fixtures = $fixturesLoader->getFixtures();

        foreach ($fixtures as $fixture) {
            $fixture->load($entityManager);
        }
    }
}
