<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Table("warehouses", indexes={
 *     @Index(columns={"title"}),
 *     @Index(columns={"city"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\WarehouseRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Warehouse
{
    use DateStorageTrait;

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private string $title = "";

    /**
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     * @ORM\ManyToOne(targetEntity="App\Entity\AdminUser")
     */
    private AdminUser $adminUser;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private string $address = "";

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private string $city = "";

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $notes = "";

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private bool $active = true;

    /**
     * @ORM\Column(type="datetime")
     */
    public DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    public DateTime $updatedAt;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
     * @param AdminUser $adminUser
     */
    public function setAdminUser(AdminUser $adminUser): void
    {
        $this->adminUser = $adminUser;
    }

    /**
     * @return AdminUser
     */
    public function getAdminUser(): AdminUser
    {
        return $this->adminUser;
    }

    /**
     * @param string $address
     */
    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $city
     */
    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @param string|null $notes
     */
    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    /**
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->notes;
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

    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt): void
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
