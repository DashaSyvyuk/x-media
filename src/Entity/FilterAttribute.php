<?php

namespace App\Entity;

use App\Repository\FilterAttributeRepository;
use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table("filter_attributes", indexes: [
    new ORM\Index(columns: ["value"])
])]
#[ORM\Entity(repositoryClass: FilterAttributeRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class FilterAttribute
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\Column(type: "string")]
    private string $value = "";

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: Filter::class, inversedBy: "attributes")]
    private ?Filter $filter;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $priority = 0;

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

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setFilter(?Filter $filter): void
    {
        $this->filter = $filter;
    }

    public function getFilter(): ?Filter
    {
        return $this->filter;
    }

    public function setPriority(?int $priority): void
    {
        $this->priority = $priority;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
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
        return $this->value;
    }
}
