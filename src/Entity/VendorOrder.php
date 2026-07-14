<?php

namespace App\Entity;

use App\Repository\VendorOrderRepository;
use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

#[ORM\Table('vendor_orders', indexes: [
    new ORM\Index(columns: ['status']),
])]
#[ORM\Entity(repositoryClass: VendorOrderRepository::class)]
#[ORM\HasLifecycleCallbacks]
class VendorOrder
{
    use DateStorageTrait;

    public const STATUS_NEW = 'new';
    public const STATUS_WAITING_PICKUP = 'waiting_pickup';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_NEW            => 'Нове',
        self::STATUS_WAITING_PICKUP => 'Чекає на відбір',
        self::STATUS_CANCELLED      => 'Анульоване',
        self::STATUS_COMPLETED      => 'Реалізоване',
    ];

    /** @var string[] */
    public const BOARD_STATUSES = [
        self::STATUS_NEW,
        self::STATUS_WAITING_PICKUP,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $id = 0;

    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Supplier::class)]
    private Supplier $supplier;

    #[ORM\Column(type: 'string')]
    private string $supplierOrderNumber = '';

    #[ORM\Column(type: 'text')]
    private string $productTitle = '';

    #[ORM\Column(type: 'integer')]
    private int $price = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'string', length: 32)]
    private string $status = self::STATUS_NEW;

    /** @var ArrayCollection<int, VendorOrderItem>|PersistentCollection<int, VendorOrderItem> $items */
    #[ORM\OneToMany(targetEntity: VendorOrderItem::class, mappedBy: 'vendorOrder', cascade: ['all'], orphanRemoval: true)]
    private ArrayCollection|PersistentCollection $items;

    #[ORM\Column(type: 'datetime')]
    public DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    public DateTime $updatedAt;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSupplier(): Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    public function getSupplierOrderNumber(): string
    {
        return $this->supplierOrderNumber;
    }

    public function setSupplierOrderNumber(string $supplierOrderNumber): void
    {
        $this->supplierOrderNumber = $supplierOrderNumber;
    }

    public function getProductTitle(): string
    {
        return $this->productTitle;
    }

    public function setProductTitle(string $productTitle): void
    {
        $this->productTitle = $productTitle;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /** @return Collection<int, VendorOrderItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(VendorOrderItem $item): void
    {
        if (! $this->items->contains($item)) {
            $item->setVendorOrder($this);
            $this->items[] = $item;
        }
    }

    public function removeItem(VendorOrderItem $item): void
    {
        if ($this->items->contains($item)) {
            $this->items->removeElement($item);
        }
    }

    public function syncFromItems(): void
    {
        $titles = [];
        $total = 0;

        foreach ($this->items as $item) {
            $title = trim($item->getTitle());
            if ($title === '') {
                continue;
            }

            $quantity = max(1, $item->getQuantity());
            $titles[] = $quantity > 1 ? sprintf('%d× %s', $quantity, $title) : $title;
            $total += $item->getLineTotal();
        }

        $this->productTitle = implode("\n", $titles);
        $this->price = $total;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
