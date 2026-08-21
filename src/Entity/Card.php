<?php

namespace App\Entity;

use App\Repository\CardRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table('cards', indexes: [
    new ORM\Index(columns: ['title']),
])]
#[ORM\Entity(repositoryClass: CardRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Card
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $id = 0;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $holderName = '';

    #[ORM\Column(type: 'string', length: 19)]
    private string $cardNumber = '';

    /** dd/mm */
    #[ORM\Column(type: 'string', length: 5)]
    private string $expiry = '';

    #[ORM\Column(type: 'datetime')]
    public DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    public DateTime $updatedAt;

    /** @var Collection<int, CardOperation> */
    #[ORM\OneToMany(targetEntity: CardOperation::class, mappedBy: 'card', cascade: ['all'], orphanRemoval: true)]
    #[ORM\OrderBy(['operatedAt' => 'DESC'])]
    private Collection $operations;

    public function __construct()
    {
        $this->operations = new ArrayCollection();
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = trim($title);
    }

    public function getHolderName(): string
    {
        return $this->holderName;
    }

    public function setHolderName(string $holderName): void
    {
        $this->holderName = trim($holderName);
    }

    public function getCardNumber(): string
    {
        return $this->cardNumber;
    }

    public function setCardNumber(string $cardNumber): void
    {
        $this->cardNumber = trim($cardNumber);
    }

    /** Formatted with spaces every 4 digits for display */
    public function getCardNumberFormatted(): string
    {
        $digits = preg_replace('/\D/', '', $this->cardNumber) ?? '';

        return implode(' ', str_split($digits, 4));
    }

    public function getExpiry(): string
    {
        return $this->expiry;
    }

    public function setExpiry(string $expiry): void
    {
        $this->expiry = trim($expiry);
    }

    /** Clipboard text: holderName / cardNumber / expiry */
    public function getClipboardText(): string
    {
        return sprintf(
            "%s\n%s\n%s",
            $this->holderName,
            $this->getCardNumberFormatted(),
            $this->expiry,
        );
    }

    /** @return Collection<int, CardOperation> */
    public function getOperations(): Collection
    {
        return $this->operations;
    }
}
