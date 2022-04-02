<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
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
    public function setCategory(Category $category)
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
    public function addAttribute(FilterAttribute $attribute)
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
        $attributes = $this->attributes->toArray();

        usort($attributes, function($a, $b)
        {
            return strcmp($a->getPriority(), $b->getPriority());
        });

        return $attributes;
    }

    /**
     * @param int $priority
     */
    public function setPriority(int $priority)
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
     * @param bool $isOpened
     */
    public function setIsOpened(bool $isOpened)
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

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt)
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

    public function __toString():string
    {
        return '' . $this->title;
    }
}
