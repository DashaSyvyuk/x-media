<?php

namespace App\Entity;

use DateTime;
use App\Traits\DateStorageTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table("return_product", indexes: [
    new ORM\Index(columns: ["status"]),
    new ORM\Index(columns: ["created_at"]),
    new ORM\Index(columns: ["updated_at"])
])]
#[ORM\Entity(repositoryClass: "App\Repository\ReturnProductRepository")]
#[ORM\HasLifecycleCallbacks()]
class ReturnProduct
{
    const STATUS_NEW = 'new';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REFUSED = 'refused';

    const STATUSES = [
        self::STATUS_NEW => 'Нове',
        self::STATUS_PROCESSING => 'В процесі отримання',
        self::STATUS_COMPLETED => 'Повернуто',
        self::STATUS_REFUSED => 'Відмовлено',
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

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $ttn = null;

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: "Supplier")]
    private Supplier $supplier;

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: "Product")]
    private Product $product;

    #[ORM\Column(type: "integer")]
    #[Assert\GreaterThanOrEqual(value: "1", message: "Too low value")]
    private int $amount = 0;

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

    public function getTtn(): ?string
    {
        return $this->ttn;
    }

    public function setTtn(?string $ttn): void
    {
        $this->ttn = $ttn;
    }

    public function setSupplier(Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
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
