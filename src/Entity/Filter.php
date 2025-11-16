<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table("filters", indexes: [
    new ORM\Index(columns: ["title"])
])]
#[ORM\Entity(repositoryClass: "App\Repository\FilterRepository")]
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

    #[ORM\OneToMany(mappedBy: "filter", targetEntity: "App\Entity\FilterAttribute", cascade: ["all"], fetch: "EAGER", orphanRemoval: true)]
    #[ORM\OrderBy(["priority" => "ASC"])]
    private $attributes;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $priority = 0;

    #[ORM\Column(type: "boolean")]
    private bool $isOpened = true;

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: "App\Entity\Category")]
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
    public function setTitle(string $title)
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
     * @param Category $category
     */
    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    /**
     * @return Category
     */
    public function getCategory(): Category
    {
        return $this->category;
    }

    /**
     * @param FilterAttribute $attribute
     */
    public function addAttribute(FilterAttribute $attribute): void
    {
        $attribute->setFilter($this);
        if (!$this->attributes->contains($attribute)) {
            $this->attributes[] = $attribute;
        }
    }

    /**
     * @param FilterAttribute $attribute
     * @return bool
     */
    public function removeAttribute(FilterAttribute $attribute): bool
    {
        return $this->attributes->removeElement($attribute);
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param int|null $priority
     */
    public function setPriority(?int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * @return int|null
     */
    public function getPriority(): ?int
    {
        return $this->priority;
    }

    /**
     * @param int $openedCount
     */
    public function setOpenedCount(int $openedCount): void
    {
        $this->openedCount = $openedCount;
    }

    /**
     * @return int
     */
    public function getOpenedCount(): int
    {
        return $this->openedCount;
    }

    /**
     * @param bool $isOpened
     */
    public function setIsOpened(bool $isOpened): void
    {
        $this->isOpened = $isOpened;
    }

    /**
     * @return bool
     */
    public function getIsOpened(): bool
    {
        return $this->isOpened;
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

    public function getFilterAttributes()
    {
        return $this->attributes;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
