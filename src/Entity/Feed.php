<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Table("feed", indexes={
 *     @Index(columns={"type"}),
 * })
 * @ORM\Entity(repositoryClass="App\Repository\FeedRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Feed
{
    use DateStorageTrait;

    const FEED_ROZETKA = 'ROZETKA';
    const FEED_PROM = 'PROM';
    const FEED_HOTLINE = 'HOTLINE';

    const TYPES = [
        'Rozetka' => self::FEED_ROZETKA,
        'Prom'    => self::FEED_PROM,
        'Hotline' => self::FEED_HOTLINE,
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /**
     * @ORM\Column(type="string")
     */
    private string $type = "";

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $cutCharacteristics = false;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $ignoreBrands = "";

    /**
     * @ORM\Column(type="datetime")
     */
    public DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    public DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): int
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
