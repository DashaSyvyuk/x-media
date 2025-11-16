<?php

namespace App\Entity;

use DateTime;
use App\Traits\DateStorageTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table("warranty", indexes: [
    new ORM\Index(columns: ["status"]),
    new ORM\Index(columns: ["created_at"]),
    new ORM\Index(columns: ["updated_at"])
])]
#[ORM\Entity(repositoryClass: "App\Repository\WarrantyRepository")]
#[ORM\HasLifecycleCallbacks()]
class Warranty
{
    const STATUS_NEW = 'new';
    const STATUS_RECEIVED_FROM_CLIENT = 'received_from_client';
    const STATUS_PROCESSING_IN_SERVICE = 'processing_service';
    const STATUS_FIXED = 'fixed';
    const STATUS_SENT_BY_NOVA_POSHTA = 'sent_by_novaposhta';
    const STATUS_COMPLETED = 'completed';
    const STATUS_NOT_FIXED = 'not_fixed';
    const STATUS_NOT_FIXED_RETURNED = 'not_fixed_returned';

    const STATUSES = [
        self::STATUS_NEW => 'Нове',
        self::STATUS_RECEIVED_FROM_CLIENT => 'Отримане від клієнта',
        self::STATUS_PROCESSING_IN_SERVICE => 'Обслуговується в сервісі',
        self::STATUS_FIXED => 'Відремонтовано',
        self::STATUS_SENT_BY_NOVA_POSHTA => 'Відправлено новою поштою',
        self::STATUS_COMPLETED => 'Завершено',
        self::STATUS_NOT_FIXED => 'Невідремонтовано. Повернуто кошти',
        self::STATUS_NOT_FIXED_RETURNED => 'Невідремонтовано. Повернуто покупцеві',
    ];

    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\Column(type: "string")]
    private string $status = "";

    #[ORM\Column(type: "string")]
    private string $name = "";

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $surname = null;

    #[ORM\Column(type: "string")]
    private string $phone = "";

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $email = null;

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: "Supplier")]
    private Supplier $supplier;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $fromClientTtn = null;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $toClientTtn = null;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $supplierOrderNumber = null;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $orderNumber = null;

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: "Product")]
    private Product $product;

    #[ORM\Column(type: "integer")]
    #[Assert\GreaterThanOrEqual(value: "1", message: "Too low value")]
    private int $expenses = 0;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $reason = "";

    #[ORM\Column(type: "datetime")]
    public DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    public DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(?string $surname): void
    {
        $this->surname = $surname;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function setSupplier(Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function getFromClientTtn(): ?string
    {
        return $this->fromClientTtn;
    }

    public function setFromClientTtn(?string $fromClientTtn): void
    {
        $this->fromClientTtn = $fromClientTtn;
    }

    public function getToClientTtn(): ?string
    {
        return $this->toClientTtn;
    }

    public function setToClientTtn(?string $toClientTtn): void
    {
        $this->toClientTtn = $toClientTtn;
    }

    public function getSupplierOrderNumber(): ?string
    {
        return $this->supplierOrderNumber;
    }

    public function setSupplierOrderNumber(?string $supplierOrderNumber): void
    {
        $this->supplierOrderNumber = $supplierOrderNumber;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(?string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getExpenses(): int
    {
        return $this->expenses;
    }

    public function setExpenses(int $expenses): void
    {
        $this->expenses = $expenses;
    }

    public function setReason(?string $reason): void
    {
        $this->reason = $reason;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function __toString(): string
    {
        return $this->product->getTitle();
    }
}
