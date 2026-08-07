<?php

namespace App\Entity;

use App\Repository\FopProfileRepository;
use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table('fop_profiles', indexes: [
    new ORM\Index(columns: ['title']),
])]
#[ORM\Entity(repositoryClass: FopProfileRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FopProfile
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $id = 0;

    #[ORM\Column(type: 'string')]
    private string $title = '';

    #[ORM\Column(type: 'string')]
    private string $bankAccount = '';

    #[ORM\Column(type: 'string')]
    private string $edrpou = '';

    #[ORM\Column(type: 'string')]
    private string $address = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: 'datetime')]
    public DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    public DateTime $updatedAt;

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

    public function getBankAccount(): string
    {
        return $this->bankAccount;
    }

    public function setBankAccount(string $bankAccount): void
    {
        $this->bankAccount = trim($bankAccount);
    }

    public function getEdrpou(): string
    {
        return $this->edrpou;
    }

    public function setEdrpou(string $edrpou): void
    {
        $this->edrpou = trim($edrpou);
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = trim($address);
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

    /**
     * Clipboard payload for payment details:
     * title / ЄДРПОУ / bank account.
     */
    public function getClipboardText(): string
    {
        return sprintf(
            "%s\nЄДРПОУ: %s\n%s",
            $this->title,
            $this->edrpou,
            $this->bankAccount,
        );
    }
}
