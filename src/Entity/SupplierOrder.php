<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table("supplier_orders")]
#[ORM\Entity(repositoryClass: "App\Repository\SupplierOrderRepository")]
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
    #[ORM\ManyToOne(targetEntity: "App\Entity\Supplier")]
    private Supplier $supplier;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?DateTime $dateTime = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?DateTime $expectedDate = null;

    #[ORM\Column(type: "boolean")]
    private bool $status = true;

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: "App\Entity\AdminUser")]
    private AdminUser $adminUser;

    #[ORM\OneToMany(
        targetEntity: "App\Entity\SupplierOrderProduct",
        mappedBy: "supplierOrder",
        cascade: ["all"],
        fetch: "EAGER",
        orphanRemoval: true
    )]
    private $products;

    #[ORM\Column(type: "datetime")]
    public DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    public DateTime $updatedAt;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param null|string $orderNumber
     */
    public function setOrderNumber(?string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    /**
     * @return string|null
     */
    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    /**
     * @param Supplier $supplier
     */
    public function setSupplier(Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    /**
     * @return Supplier
     */
    public function getSupplier(): Supplier
    {
        return $this->supplier;
    }

    /**
     * @param DateTime|null $dateTime
     */
    public function setDateTime(?DateTime $dateTime): void
    {
        $this->dateTime = $dateTime;
    }

    /**
     * @return DateTime|null
     */
    public function getDateTime(): ?DateTime
    {
        return $this->dateTime;
    }

    /**
     * @param DateTime|null $expectedDate
     */
    public function setExpectedDate(?DateTime $expectedDate): void
    {
        $this->expectedDate = $expectedDate;
    }

    /**
     * @return DateTime|null
     */
    public function getExpectedDate(): ?DateTime
    {
        return $this->expectedDate;
    }

    /**
     * @param bool $status
     */
    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        return $this->status;
    }

    /**
     * @param AdminUser $adminUser
     */
    public function setAdminUser(AdminUser $adminUser): void
    {
        $this->adminUser = $adminUser;
    }

    /**
     * @return AdminUser
     */
    public function getAdminUser(): AdminUser
    {
        return $this->adminUser;
    }

    /**
     * @param SupplierOrderProduct $product
     */
    public function addProduct(SupplierOrderProduct $product): void
    {
        $product->setSupplierOrder($this);
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
        }
    }

    /**
     * @param SupplierOrderProduct $product
     * @return bool
     */
    public function removeProduct(SupplierOrderProduct $product): bool
    {
        return $this->products->removeElement($product);
    }

    public function getProducts()
    {
        return $this->products;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
