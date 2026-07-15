<?php

namespace App\Entity;

use App\Repository\VendorOrderItemRepository;
use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table('vendor_order_items')]
#[ORM\Entity(repositoryClass: VendorOrderItemRepository::class)]
#[ORM\HasLifecycleCallbacks]
class VendorOrderItem
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $id = 0;

    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: VendorOrder::class, inversedBy: 'items')]
    private VendorOrder $vendorOrder;

    #[ORM\Column(type: 'text')]
    private string $title = '';

    #[ORM\Column(type: 'integer')]
    private int $price = 0;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $quantity = 1;

    #[ORM\Column(type: 'datetime')]
    public DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    public DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getVendorOrder(): VendorOrder
    {
        return $this->vendorOrder;
    }

    public function setVendorOrder(VendorOrder $vendorOrder): void
    {
        $this->vendorOrder = $vendorOrder;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = max(1, $quantity);
    }

    public function getLineTotal(): int
    {
        return $this->price * max(1, $this->quantity);
    }
}
