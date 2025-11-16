<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table("rozetka_characteristics_values")]
#[ORM\Entity(repositoryClass: "App\Repository\RozetkaCharacteristicsValueRepository")]
#[ORM\HasLifecycleCallbacks()]
class RozetkaCharacteristicsValue
{
    use DateStorageTrait;

    #[ORM\Id()]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\JoinColumn(name: "characteristic_id", referencedColumnName: "rozetka_id", nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: "RozetkaCharacteristics", inversedBy: "values")]
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

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param null|RozetkaCharacteristics $characteristic
     */
    public function setCharacteristic(?RozetkaCharacteristics $characteristic): void
    {
        $this->characteristic = $characteristic;
    }

    /**
     * @return RozetkaCharacteristics|null
     */
    public function getCharacteristic(): ?RozetkaCharacteristics
    {
        return $this->characteristic;
    }

    /**
     * @param string $rozetkaId
     */
    public function setRozetkaId(string $rozetkaId): void
    {
        $this->rozetkaId = $rozetkaId;
    }

    /**
     * @return string
     */
    public function getRozetkaId(): string
    {
        return $this->rozetkaId;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return bool
     */
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
