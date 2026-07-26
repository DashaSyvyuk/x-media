<?php

namespace App\EventListener;

use App\Entity\ProductImage;
use App\Service\BunnyStorageClient;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Psr\Log\LoggerInterface;
use Throwable;

class ProductImageUploadSubscriber
{
    /**
     * @var list<ProductImage>
     */
    private array $queue = [];

    public function __construct(
        private BunnyStorageClient $bunny,
        private string $uploadDir,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function ensureLocalDir(): void
    {
        if (! is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0775, true);
        }
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

        $this->ensureLocalDir();

        $localPath  = $this->uploadDir . '/' . $entity->getImageUrl();
        $remotePath = 'products/' . $entity->getImageUrl();

        if (! file_exists($localPath)) {
            return;
        }

        try {
            $this->bunny->upload($localPath, $remotePath);
            @unlink($localPath);
        } catch (Throwable $e) {
            // Keep the local file so the admin can still see/retry; do not break the save response.
            $this->logger?->error('Product image Bunny upload failed: {message}', [
                'message' => $e->getMessage(),
                'file'    => $entity->getImageUrl(),
            ]);
        }
    }
}
