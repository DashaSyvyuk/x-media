<?php

namespace App\Entity;

use App\Repository\WarehouseRepository;
use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table("warehouses", indexes: [
    new ORM\Index(columns: ["title"]),
    new ORM\Index(columns: ["city"])
])]
#[ORM\Entity(repositoryClass: WarehouseRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class Warehouse
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\Column(type: "string")]
    private string $title = "";

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: AdminUser::class)]
    private AdminUser $adminUser;

    #[ORM\Column(type: "string")]
    private string $address = "";

    #[ORM\Column(type: "string")]
    private string $city = "";

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $notes = "";

    #[ORM\Column(type: "boolean")]
    private bool $active = true;

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

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setAdminUser(AdminUser $adminUser): void
    {
        $this->adminUser = $adminUser;
    }

    public function getAdminUser(): AdminUser
    {
        return $this->adminUser;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
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

    public function getStockQtySum(): ?int
    {
        return null;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
