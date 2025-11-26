<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table("nova_poshta_city", indexes: [
    new ORM\Index(columns: ["title"]),
    new ORM\Index(columns: ["ref"]),
    new ORM\Index(columns: ["created_at"]),
])]
#[ORM\Entity(repositoryClass: "App\Repository\NovaPoshtaCityRepository")]
#[ORM\HasLifecycleCallbacks()]
class NovaPoshtaCity
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\Column(type: "string")]
    private string $ref = "";

    #[ORM\Column(type: "string")]
    private string $title = "";

    #[ORM\OneToMany(mappedBy: "city", targetEntity: "NovaPoshtaOffice", cascade: ["all"], orphanRemoval: true)]
    private $offices;

    #[ORM\Column(type: "datetime")]
    private DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    private DateTime $updatedAt;

    public function __construct()
    {
        $this->offices = new ArrayCollection();
    }

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

    public function getOffices()
    {
        return $this->offices;
    }

    public function setOffices($offices)
    {
        if (count($offices) > 0) {
            foreach ($offices as $office) {
                $this->addOffice($office);
            }
        }
    }

    public function addOffice(NovaPoshtaOffice $office)
    {
        if (!$this->offices->contains($office)) {
            $office->setCity($this);
            $this->offices[] = $office;
        }
    }

    public function removeOffice(NovaPoshtaOffice $office)
    {
        if ($this->offices->contains($office)) {
            $this->offices->removeElement($office);
        }
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

    public function __toString(): string
    {
        return (string) $this->title;
    }
}
