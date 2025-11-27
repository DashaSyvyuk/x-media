<?php

namespace App\Entity;

use App\Repository\FilterRepository;
use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

#[ORM\Table("filters", indexes: [
    new ORM\Index(columns: ["title"])
])]
#[ORM\Entity(repositoryClass: FilterRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class Filter
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\Column(type: "string")]
    private string $title = "";

    /** @var ArrayCollection<int, FilterAttribute>|PersistentCollection<int, FilterAttribute> $attributes */
    #[ORM\OneToMany(
        targetEntity: FilterAttribute::class,
        mappedBy: "filter",
        cascade: ["all"],
        fetch: "EAGER",
        orphanRemoval: true
    )]
    #[ORM\OrderBy(["priority" => "ASC"])]
    private ArrayCollection|PersistentCollection $attributes;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $priority = 0;

    #[ORM\Column(type: "boolean")]
    private bool $isOpened = true;

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: Category::class)]
    private Category $category;

    #[ORM\Column(type: "integer")]
    private int $openedCount = 0;

    #[ORM\Column(type: "datetime")]
    public DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    public DateTime $updatedAt;

    public function __construct()
    {
        $this->attributes = new ArrayCollection();
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function addAttribute(FilterAttribute $attribute): void
    {
        $attribute->setFilter($this);
        if (!$this->attributes->contains($attribute)) {
            $this->attributes[] = $attribute;
        }
    }

    public function removeAttribute(FilterAttribute $attribute): bool
    {
        return $this->attributes->removeElement($attribute);
    }

    /**
     * @return ArrayCollection<int, FilterAttribute>|PersistentCollection<int, FilterAttribute>
     */
    public function getAttributes(): ArrayCollection|PersistentCollection
    {
        return $this->attributes;
    }

    public function setPriority(?int $priority): void
    {
        $this->priority = $priority;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setOpenedCount(int $openedCount): void
    {
        $this->openedCount = $openedCount;
    }

    public function getOpenedCount(): int
    {
        return $this->openedCount;
    }

    public function setIsOpened(bool $isOpened): void
    {
        $this->isOpened = $isOpened;
    }

    public function getIsOpened(): bool
    {
        return $this->isOpened;
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

    /**
     * @return ArrayCollection<int, FilterAttribute>|PersistentCollection<int, FilterAttribute>
     */
    public function getFilterAttributes(): ArrayCollection|PersistentCollection
    {
        return $this->attributes;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
