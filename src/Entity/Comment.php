<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table("comment", indexes: [
    new ORM\Index(columns: ["email"]),
    new ORM\Index(columns: ["created_at"]),
])]
#[ORM\Entity()]
#[ORM\HasLifecycleCallbacks()]
class Comment
{
    use DateStorageTrait;

    const STATUS_NEW = 'NEW';
    const STATUS_CONFIRMED = 'CONFIRMED';
    const STATUS_DISABLED = 'DISABLED';

    const STATUSES = [
        'Новий'         => self::STATUS_NEW,
        'Підтверджено'  => self::STATUS_CONFIRMED,
        'Заблокований'  => self::STATUS_DISABLED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\Column(type: "string")]
    private string $author = "";

    #[ORM\Column(type: "string")]
    private string $status = "";

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $email = "";

    #[ORM\Column(type: "text")]
    private string $comment = "";

    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[ORM\ManyToOne(targetEntity: "Product", inversedBy: "comments")]
    private Product $product;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $answer;

    #[ORM\JoinColumn(nullable: true, onDelete: "CASCADE")]
    #[ORM\OneToOne(targetEntity: "App\Entity\ProductRating")]
    private ?ProductRating $productRating;

    #[ORM\Column(type: "datetime")]
    public DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    public DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): int
    {
        $this->id = $id;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    public function getProductRating(): ?ProductRating
    {
        return $this->productRating;
    }

    public function setProductRating(?ProductRating $productRating): void
    {
        $this->productRating = $productRating;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(?string $answer): void
    {
        $this->answer = $answer;
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

    public function __toString():string
    {
        return $this->comment;
    }
}
