<?php

namespace App\EventListener;

use App\Entity\ProductImage;
use App\Service\BunnyStorageClient;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

class ProductImageUploadSubscriber
{
    /**
     * @var array<object>
     */
    private array $queue = [];

    public function __construct(
        private BunnyStorageClient $bunny,
        private string $uploadDir
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->collect($args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->collect($args->getObject());
    }

    private function collect(object $entity): void
    {
        if ($entity instanceof ProductImage) {
            $this->queue[] = $entity;
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        foreach ($this->queue as $entity) {
            $this->upload($entity);
        }

        $this->queue = [];
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof ProductImage && $entity->getImageUrl()) {
            $this->bunny->delete('products/' . $entity->getImageUrl());
        }
    }

    private function upload(ProductImage $entity): void
    {
        if (! $entity->getImageUrl()) {
            return;
        }

        $localPath  = $this->uploadDir . '/' . $entity->getImageUrl();
        $remotePath = 'products/' . $entity->getImageUrl();

        if (! file_exists($localPath)) {
            return;
        }

        $this->bunny->upload($localPath, $remotePath);

        unlink($localPath);
    }
}
