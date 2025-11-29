<?php

namespace App\Tests\Traits;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;

trait FixturesTrait
{
    protected function loadFixtures(): void
    {
        $container = static::getContainer();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();

        // Get the fixture loader
        $fixturesLoader = $container->get('doctrine.fixtures.loader');
        $fixtures = $fixturesLoader->getFixtures();

        // Use ORMExecutor to properly load fixtures with reference support
        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute($fixtures, false); // false = append mode (don't purge before loading)
    }
}
