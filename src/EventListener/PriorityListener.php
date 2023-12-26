<?php

namespace App\EventListener;

use App\Entity\Filter;
use App\Entity\FilterAttribute;
use App\Entity\Slider;
use App\Repository\FilterAttributeRepository;
use App\Repository\FilterRepository;
use App\Repository\SliderRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
class PriorityListener
{
    public function __construct(
        private readonly FilterRepository $filterRepository,
        private readonly FilterAttributeRepository $filterAttributeRepository,
        private readonly SliderRepository $sliderRepository,
    )
    {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();

        if ($entity instanceof Filter) {
            $filter = $this->filterRepository->findOneBy([], ['priority' => 'DESC']);

            $entity->setPriority($filter->getPriority() + 1);
            $entityManager->flush();
        }

        if ($entity instanceof FilterAttribute) {
            $filterAttribute = $this->filterAttributeRepository->findOneBy([], ['priority' => 'DESC']);

            $entity->setPriority($filterAttribute->getPriority() + 1);
            $entityManager->flush();
        }

        if ($entity instanceof Slider) {
            $slider = $this->sliderRepository->findOneBy([], ['priority' => 'DESC']);

            $entity->setPriority($slider->getPriority() + 1);
            $entityManager->flush();
        }
    }
}
