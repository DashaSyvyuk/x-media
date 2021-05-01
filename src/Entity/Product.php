<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Table("product", indexes={
 *     @Index(columns={"status"}),
 *     @Index(columns={"title"}),
 *     @Index(columns={"created_at"}),
 *     @Index(columns={"updated_at"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Product
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
    private $status;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $title;

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     * @ORM\ManyToOne(targetEntity="App\Entity\Category")
     */
    private $category;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\FilterParameterValue", mappedBy="product", cascade={"all"}, orphanRemoval=true, fetch="EAGER")
     */
    private $filterParameterValues;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Entity\ProductImage", mappedBy="product", cascade={"all"}, orphanRemoval=true, fetch="EAGER")
     */
    private $images;

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
        $this->filterParameterValues = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status)
    {
        $this->status = $status;
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
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param ?Category $category
     */
    public function setCategory(?Category $category)
    {
        $this->category = $category;
    }

    /**
     * @return Category|null
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * @param FilterParameterValue $filterParameterValue
     */
    public function addFilterParameterValue(FilterParameterValue $filterParameterValue)
    {
        $filterParameterValue->setProduct($this);
        if (!$this->filterParameterValues->contains($filterParameterValue)) {
            $this->filterParameterValues[] = $filterParameterValue;
        }
    }

    /**
     * @param FilterParameterValue $filterParameterValue
     * @return bool
     */
    public function removeFilterParameterValue(FilterParameterValue $filterParameterValue): bool
    {
        return $this->filterParameterValues->removeElement($filterParameterValue);
    }

    public function getFilterParameterValues(): ArrayCollection
    {
        return $this->filterParameterValues;
    }

    /**
     * @return array
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param ProductImage $image
     * @return Product
     */
    public function addImage(ProductImage $image)
    {
        $image->setProduct($this);
        $this->images[] = $image;
    }

    /**
     * @param ProductImage $image
     */
    public function removeImage($image)
    {
        if ($this->images->contains($image)) {
            $this->images->removeElement($image);
        }
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
