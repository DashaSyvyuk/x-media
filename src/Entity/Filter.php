<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
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
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $title = "";

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\FilterAttribute",
     *     mappedBy="filter", cascade={"all"}, orphanRemoval=true, fetch="EAGER")
     */
    private $attributes;

    /**
     * @ORM\Column(type="datetime")
     */
    public $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    public $updatedAt;

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
        return $this->attributes;
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
