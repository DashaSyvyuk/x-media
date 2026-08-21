<?php

namespace App\Entity;

use App\Repository\CardOperationRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table('card_operations', indexes: [
    new ORM\Index(columns: ['card_id', 'operated_at']),
    new ORM\Index(columns: ['is_done']),
])]
#[ORM\Entity(repositoryClass: CardOperationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CardOperation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: Card::class, inversedBy: 'operations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Card $card;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $amount = '0.00';

    #[ORM\Column(type: 'datetime')]
    private DateTime $operatedAt;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isDone = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: 'datetime')]
    public DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    public DateTime $updatedAt;

    public function __construct()
    {
        $this->operatedAt = new DateTime();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCard(): Card
    {
        return $this->card;
    }

    public function setCard(Card $card): void
    {
        $this->card = $card;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getAmountFloat(): float
    {
        return (float) $this->amount;
    }

    public function setAmount(string|float|int $amount): void
    {
        $this->amount = number_format((float) $amount, 2, '.', '');
    }

    public function getOperatedAt(): DateTime
    {
        return $this->operatedAt;
    }

    public function setOperatedAt(DateTime $operatedAt): void
    {
        $this->operatedAt = $operatedAt;
    }

    public function isDone(): bool
    {
        return $this->isDone;
    }

    public function setIsDone(bool $isDone): void
    {
        $this->isDone = $isDone;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $note = $note !== null ? trim($note) : null;
        $this->note = $note !== '' ? $note : null;
    }
}
