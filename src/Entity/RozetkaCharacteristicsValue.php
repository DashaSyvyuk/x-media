<?php

namespace App\Entity;

use App\Repository\RozetkaCharacteristicsValueRepository;
use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table("rozetka_characteristics_values")]
#[ORM\Entity(repositoryClass: RozetkaCharacteristicsValueRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class RozetkaCharacteristicsValue
{
    use DateStorageTrait;

    #[ORM\Id()]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\JoinColumn(
        name: "characteristic_id",
        referencedColumnName: "rozetka_id",
        nullable: true,
        onDelete: "SET NULL"
    )]
    #[ORM\ManyToOne(targetEntity: RozetkaCharacteristics::class, inversedBy: "values")]
    private RozetkaCharacteristics $characteristic;

    #[ORM\Column(type: "string")]
    private string $rozetkaId = "";

    #[ORM\Column(type: "string")]
    private string $title = "";

    #[ORM\Column(type: "boolean")]
    private bool $active = true;

    #[ORM\Column(type: "datetime")]
    public DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    public DateTime $updatedAt;

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setCharacteristic(?RozetkaCharacteristics $characteristic): void
    {
        $this->characteristic = $characteristic;
    }

    public function getCharacteristic(): ?RozetkaCharacteristics
    {
        return $this->characteristic;
    }

    public function setRozetkaId(string $rozetkaId): void
    {
        $this->rozetkaId = $rozetkaId;
    }

    public function getRozetkaId(): string
    {
        return $this->rozetkaId;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getActive(): bool
    {
        return $this->active;
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
        return $this->title;
    }
}
