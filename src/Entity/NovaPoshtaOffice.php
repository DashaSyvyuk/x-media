<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("nova_poshta_office")
 * @ORM\Entity(repositoryClass="App\Repository\NovaPoshtaOfficeRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class NovaPoshtaOffice
{
    use DateStorageTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /**
     * @ORM\Column(type="string")
     */
    private string $ref = "";

    /**
     * @ORM\Column(type="string")
     */
    private string $title = "";

    /**
     * @ORM\ManyToOne(targetEntity="NovaPoshtaCity")
     */
    private $city;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): int
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

    public function setRef(string $ref): void
    {
        $this->ref = $ref;
    }

    public function getRef(): string
    {
        return $this->ref;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setCity(NovaPoshtaCity $city)
    {
        $this->city = $city;
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

    public function __toString():string
    {
        return (string) $this->title;
    }
}
