<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Table("filters", indexes={
 *     @Index(columns={"title"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\FilterRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Filter
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
     * @ORM\OneToMany(targetEntity="App\Entity\FilterAttribute",
     *     mappedBy="filter", cascade={"all"}, orphanRemoval=true, fetch="EAGER")
     */
    private $attributes;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private int $priority = 0;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private bool $isOpened = true;

    /**
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     * @ORM\ManyToOne(targetEntity="App\Entity\Category")
     */
    private Category $category;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private int $openedCount = 0;

    /**
     * @ORM\Column(type="datetime")
     */
    public DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
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
     * @param int $priority
     */
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param int $openedCount
     */
    public function setOpenedCount(int $openedCount)
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

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function getFilterAttributes()
    {
        $attributes = $this->attributes->toArray();

        usort($attributes, function($a, $b) {
            return strcmp($a->getPriority(), $b->getPriority());
        });

        return $attributes;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
