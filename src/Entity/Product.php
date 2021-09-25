<?php

namespace App\Entity;

use DateTime;
use App\Traits\DateStorageTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\ManyToMany;

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
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /**
     * @ORM\Column(type="string")
     */
    private string $status = "";

    /**
     * @ORM\Column(type="integer")
     */
    private int $price = 0;

    /**
     * @ORM\Column(type="string")
     */
    private ?string $title = "";

    /**
     * @ORM\Column(type="text")
     */
    private ?string $description = "";

    /**
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     * @ORM\ManyToOne(targetEntity="App\Entity\Category")
     */
    private Category $category;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ProductImage", mappedBy="product", cascade={"all"}, orphanRemoval=true)
     */
    private $images;

    /**
     * @ManyToMany(targetEntity="App\Entity\FilterAttribute")
     * @ORM\JoinTable(name="product_filter_attribute",
     *      joinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="filter_attribute_id", referencedColumnName="id")}
     *      )
     */
    private $filterAttributes;

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
        $this->images = new ArrayCollection();
        $this->filterAttributes = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getImages()
    {
        return $this->images;
    }

    public function setImages($images)
    {
        if (count($images) > 0) {
            foreach ($images as $image) {
                $this->addImage($image);
            }
        }

    }

    /**
     * @param ProductImage $image
     */
    public function addImage(ProductImage $image)
    {
        $image->setProduct($this);
        $this->images[] = $image;
    }

    /**
     * @param ProductImage $image
     */
    public function removeImage(ProductImage $image)
    {
        if ($this->images->contains($image)) {
            $this->images->removeElement($image);
        }
    }

    /**
     * @param FilterAttribute $filterAttribute
     */
    public function addFilterAttribute(FilterAttribute $filterAttribute)
    {
        if ($this->filterAttributes->contains($filterAttribute)) {
            return;
        }

        $this->filterAttributes->add($filterAttribute);
    }

    /**
     * @param FilterAttribute $filterAttribute
     */
    public function removeFilterAttribute(FilterAttribute $filterAttribute)
    {
        if (!$this->filterAttributes->contains($filterAttribute)) {
            return;
        }

        $this->filterAttributes->removeElement($filterAttribute);
    }

    public function getFilterAttributes()
    {
        return $this->filterAttributes;
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
        return '' . $this->title;
    }
}
