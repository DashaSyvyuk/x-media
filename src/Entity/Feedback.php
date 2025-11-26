<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table("feedback", indexes: [
    new ORM\Index(columns: ["email"]),
    new ORM\Index(columns: ["created_at"]),
])]
#[ORM\Entity(repositoryClass: "App\Repository\FeedbackRepository")]
#[ORM\HasLifecycleCallbacks()]
class Feedback
{
    use DateStorageTrait;

    public const STATUS_NEW = 'NEW';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_DISABLED = 'DISABLED';

    public const STATUSES = [
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

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $answer;

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

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(?string $answer)
    {
        $this->answer = $answer;
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
}
