<?php

namespace App\Entity;

use App\Repository\OrderFulfillmentLinkRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table('order_fulfillment_links', indexes: [
    new ORM\Index(columns: ['link_group']),
    new ORM\Index(columns: ['rozetka_order_id']),
])]
#[ORM\Entity(repositoryClass: OrderFulfillmentLinkRepository::class)]
class OrderFulfillmentLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $id = 0;

    #[ORM\Column(type: 'string', length: 16)]
    private string $linkGroup = '';

    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: VendorOrder::class)]
    private VendorOrder $vendorOrder;

    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Order::class)]
    private ?Order $order = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['unsigned' => true])]
    private ?int $rozetkaOrderId = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getLinkGroup(): string
    {
        return $this->linkGroup;
    }

    public function setLinkGroup(string $linkGroup): void
    {
        $this->linkGroup = $linkGroup;
    }

    public function getVendorOrder(): VendorOrder
    {
        return $this->vendorOrder;
    }

    public function setVendorOrder(VendorOrder $vendorOrder): void
    {
        $this->vendorOrder = $vendorOrder;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): void
    {
        $this->order = $order;
    }

    public function getRozetkaOrderId(): ?int
    {
        return $this->rozetkaOrderId;
    }

    public function setRozetkaOrderId(?int $rozetkaOrderId): void
    {
        $this->rozetkaOrderId = $rozetkaOrderId;
    }
}
