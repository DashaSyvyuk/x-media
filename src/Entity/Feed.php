<?php

namespace App\Entity;

use App\Repository\FeedRepository;
use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table("feed", indexes: [
    new ORM\Index(columns: ["type"]),
])]
#[ORM\Entity(repositoryClass: FeedRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class Feed
{
    use DateStorageTrait;

    public const FEED_ROZETKA = 'ROZETKA';
    public const FEED_PROM = 'PROM';
    public const FEED_HOTLINE = 'HOTLINE';
    public const FEED_EKATALOG = 'EKATALOG';

    public const TYPES = [
        'Rozetka'   => self::FEED_ROZETKA,
        'Prom'      => self::FEED_PROM,
        'Hotline'   => self::FEED_HOTLINE,
        'E-Katalog' => self::FEED_EKATALOG,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\Column(type: "string")]
    private string $type = "";

    #[ORM\Column(type: "boolean")]
    private bool $cutCharacteristics = false;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $ignoreBrands = "";

    #[ORM\Column(type: "integer")]
    private int $ourPercent = 0;

    #[ORM\Column(type: "integer")]
    private int $fee = 0;

    #[ORM\Column(type: "datetime")]
    public DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    public DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setCutCharacteristics(bool $cutCharacteristics): void
    {
        $this->cutCharacteristics = $cutCharacteristics;
    }

    public function getCutCharacteristics(): bool
    {
        return $this->cutCharacteristics;
    }

    public function setIgnoreBrands(?string $ignoreBrands): void
    {
        $this->ignoreBrands = $ignoreBrands;
    }

    public function getIgnoreBrands(): ?string
    {
        return $this->ignoreBrands;
    }

    public function setOurPercent(int $ourPercent): void
    {
        $this->ourPercent = $ourPercent;
    }

    public function getOurPercent(): int
    {
        return $this->ourPercent;
    }

    public function setFee(int $fee): void
    {
        $this->fee = $fee;
    }

    public function getFee(): int
    {
        return $this->fee;
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

    public function __toString(): string
    {
        return $this->type;
    }
}
