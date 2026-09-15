<?php

namespace App\EventListener;

use App\Entity\Category;
use App\Entity\DeliveryType;
use App\Entity\PaymentType;
use App\Entity\Slider;
use App\Service\BunnyStorageClient;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Psr\Log\LoggerInterface;
use Throwable;

class ImageUploadSubscriber
{
    /**
     * Map of entity FQCN => [folder, getter]. The getter returns the filename
     * stored in the entity (no path), which is also the file name on disk
     * inside the corresponding `public/images/{folder}` directory.
     *
     * @var array<class-string, array{folder: string, getter: string}>
     */
    private const ENTITY_MAP = [
        Slider::class       => ['folder' => 'slider',   'getter' => 'getImageUrl'],
        Category::class     => ['folder' => 'category', 'getter' => 'getImage'],
        DeliveryType::class => ['folder' => 'delivery', 'getter' => 'getIcon'],
        PaymentType::class  => ['folder' => 'payment',  'getter' => 'getIcon'],
    ];

    /**
     * @var array<object>
     */
    private array $queue = [];

    public function __construct(
        private BunnyStorageClient $bunny,
        private string $publicImagesDir,
        private string $cdnUrl,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Ensures the local upload directory for the given entity class exists.
     *
     * EasyAdmin's `FileUploadType` validates that the configured upload dir
     * exists and is writable when building the form. The four directories
     * here (`slider`, `category`, `delivery`, `payment`) are git-ignored, so
     * they are missing on a fresh checkout/container and break the admin
     * form before anything is uploaded. Calling this from the corresponding
     * CRUD controller's `configureFields` makes the requirement explicit and
     * keeps the folder mapping in one place.
     *
     * @param class-string $entityClass
     */
    public function ensureLocalDirFor(string $entityClass): void
    {
        if (! isset(self::ENTITY_MAP[$entityClass])) {
            return;
        }

        $path = $this->publicImagesDir . '/' . self::ENTITY_MAP[$entityClass]['folder'];

        if (! is_dir($path)) {
            @mkdir($path, 0775, true);
        }
    }

    /**
     * Returns the Bunny CDN folder URL for the given entity class, suitable
     * for EasyAdmin's `ImageField::setBasePath()` so that admin index/edit
     * pages render images directly from Bunny instead of the now-purged
     * local copy.
     *
     * @param class-string $entityClass
     */
    public function cdnBaseUrlFor(string $entityClass): string
    {
        if (! isset(self::ENTITY_MAP[$entityClass])) {
            return '';
        }

        return rtrim($this->cdnUrl, '/') . '/' . self::ENTITY_MAP[$entityClass]['folder'] . '/';
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
        if ($this->resolve($entity) !== null) {
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
        $config = $this->resolve($entity);

        if ($config === null) {
            return;
        }

        $fileName = $entity->{$config['getter']}();

        if ($fileName) {
            $this->bunny->delete($config['folder'] . '/' . $fileName);
        }
    }

    private function upload(object $entity): void
    {
        $config = $this->resolve($entity);

        if ($config === null) {
            return;
        }

        $fileName = $entity->{$config['getter']}();

        if (! $fileName) {
            return;
        }

        $localPath  = $this->publicImagesDir . '/' . $config['folder'] . '/' . $fileName;
        $remotePath = $config['folder'] . '/' . $fileName;

        if (! file_exists($localPath)) {
            return;
        }

        try {
            $this->bunny->upload($localPath, $remotePath);
            // Remove local staging copy only after a successful CDN upload.
            // On failure keep the file so admin preview / retry still works.
            @unlink($localPath);
        } catch (Throwable $e) {
            $this->logger?->error('Entity image Bunny upload failed: {message}', [
                'message' => $e->getMessage(),
                'file'    => $fileName,
                'folder'  => $config['folder'],
            ]);
        }
    }

    /**
     * @return array{folder: string, getter: string}|null
     */
    private function resolve(object $entity): ?array
    {
        foreach (self::ENTITY_MAP as $class => $config) {
            if ($entity instanceof $class) {
                return $config;
            }
        }

        return null;
    }
}
