<?php

namespace App\Entity;

use App\Repository\PlanningGoodRepository;
use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'planning_goods')]
#[ORM\Entity(repositoryClass: PlanningGoodRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PlanningGood
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: PlanningGoodBatch::class, inversedBy: 'goods')]
    #[ORM\JoinColumn(name: 'planning_good_batch_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?PlanningGoodBatch $batch = null;

    #[ORM\ManyToOne(targetEntity: Warehouse::class)]
    #[ORM\JoinColumn(name: 'warehouse_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Warehouse $warehouse = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name = '';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $purchasePrice = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, options: ['default' => 0])]
    private string $deliveryPrice = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $salePrice = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isSold = false;

    #[ORM\Column(type: 'datetime')]
    public DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    public DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getBatch(): ?PlanningGoodBatch
    {
        return $this->batch;
    }

    public function setBatch(?PlanningGoodBatch $batch): void
    {
        $this->batch = $batch;
    }

    public function getWarehouse(): ?Warehouse
    {
        return $this->warehouse;
    }

    public function setWarehouse(?Warehouse $warehouse): void
    {
        $this->warehouse = $warehouse;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = trim($name);
    }

    public function getPurchasePrice(): float
    {
        return (float) $this->purchasePrice;
    }

    public function setPurchasePrice(float|string $purchasePrice): void
    {
        $this->purchasePrice = number_format((float) $purchasePrice, 2, '.', '');
    }

    public function getDeliveryPrice(): float
    {
        return (float) $this->deliveryPrice;
    }

    public function setDeliveryPrice(float|string $deliveryPrice): void
    {
        $this->deliveryPrice = number_format((float) $deliveryPrice, 2, '.', '');
    }

    public function getSalePrice(): ?float
    {
        return $this->salePrice === null ? null : (float) $this->salePrice;
    }

    public function setSalePrice(float|string|null $salePrice): void
    {
        $this->salePrice = $salePrice === null || $salePrice === ''
            ? null
            : number_format((float) $salePrice, 2, '.', '');
    }

    public function isSold(): bool
    {
        return $this->isSold;
    }

    public function setIsSold(bool $isSold): void
    {
        $this->isSold = $isSold;
    }

    public function getMargin(): ?float
    {
        if (! $this->isSold || $this->salePrice === null) {
            return null;
        }

        return $this->getSalePrice() - $this->getPurchasePrice() - $this->getDeliveryPrice();
    }

    public function getTotalPurchaseValue(): float
    {
        return $this->getPurchasePrice() + $this->getDeliveryPrice();
    }

    public function getTotalSaleValue(): float
    {
        if (! $this->isSold || $this->salePrice === null) {
            return 0.0;
        }

        return $this->getSalePrice();
    }
}
