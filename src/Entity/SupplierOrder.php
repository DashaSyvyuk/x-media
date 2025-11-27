<?php

namespace App\Entity;

use App\Repository\SupplierOrderRepository;
use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

#[ORM\Table("supplier_orders")]
#[ORM\Entity(repositoryClass: SupplierOrderRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class SupplierOrder
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $orderNumber = null;

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: Supplier::class)]
    private Supplier $supplier;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?DateTime $dateTime = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?DateTime $expectedDate = null;

    #[ORM\Column(type: "boolean")]
    private bool $status = true;

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: AdminUser::class)]
    private AdminUser $adminUser;

    /** @var ArrayCollection<int, SupplierOrderProduct>|PersistentCollection<int, SupplierOrderProduct> $products */
    #[ORM\OneToMany(
        targetEntity: SupplierOrderProduct::class,
        mappedBy: "supplierOrder",
        cascade: ["all"],
        fetch: "EAGER",
        orphanRemoval: true
    )]
    private ArrayCollection|PersistentCollection $products;

    #[ORM\Column(type: "datetime")]
    public DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    public DateTime $updatedAt;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setOrderNumber(?string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setSupplier(Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    public function getSupplier(): Supplier
    {
        return $this->supplier;
    }

    public function setDateTime(?DateTime $dateTime): void
    {
        $this->dateTime = $dateTime;
    }

    public function getDateTime(): ?DateTime
    {
        return $this->dateTime;
    }

    public function setExpectedDate(?DateTime $expectedDate): void
    {
        $this->expectedDate = $expectedDate;
    }

    public function getExpectedDate(): ?DateTime
    {
        return $this->expectedDate;
    }

    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): bool
    {
        return $this->status;
    }

    public function setAdminUser(AdminUser $adminUser): void
    {
        $this->adminUser = $adminUser;
    }

    public function getAdminUser(): AdminUser
    {
        return $this->adminUser;
    }

    public function addProduct(SupplierOrderProduct $product): void
    {
        $product->setSupplierOrder($this);
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
        }
    }

    public function removeProduct(SupplierOrderProduct $product): bool
    {
        return $this->products->removeElement($product);
    }

    /**
     * @return ArrayCollection<int, SupplierOrderProduct>|PersistentCollection<int, SupplierOrderProduct>
     */
    public function getProducts(): ArrayCollection|PersistentCollection
    {
        return $this->products;
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
