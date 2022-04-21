<?php

namespace App\Entity;

use DateTime;
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
     * @ORM\Column(type="integer")
     */
    private int $priceWithVAT = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private int $deliveryCost = 0;

    /**
     * @ORM\Column(type="string")
     */
    private ?string $title = "";

    /**
     * @ORM\Column(type="text")
     */
    private ?string $description = "";

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $note = "";

    /**
     * @ORM\Column(type="string")
     */
    private ?string $metaKeyword = "";

    /**
     * @ORM\Column(type="string")
     */
    private ?string $metaDescription = "";

    /**
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="products")
     */
    private Category $category;

    /**
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     * @ORM\ManyToOne(targetEntity="Currency")
     */
    private Currency $currency;

    /**
     * @ORM\OneToMany(targetEntity="ProductImage", mappedBy="product", cascade={"all"}, orphanRemoval=true)
     */
    private $images;

    /**
     * @ORM\OneToMany(targetEntity="ProductCharacteristic", mappedBy="product", cascade={"all"}, orphanRemoval=true)
     */
    private $characteristics;

    /**
     * @ORM\OneToMany(targetEntity="ProductFilterAttribute", mappedBy="product", cascade={"all"}, orphanRemoval=true)
     */
    private $filterAttributes;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="product", cascade={"all"}, orphanRemoval=true)
     */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity="ProductRating", mappedBy="product", cascade={"all"}, orphanRemoval=true)
     */
    private $ratings;

    /**
     * @ORM\Column(type="string")
     */
    private ?string $productCode = "";

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
        $this->characteristics = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->ratings = new ArrayCollection();
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

    public function getPriceWithVAT(): int
    {
        return $this->priceWithVAT;
    }

    public function setPriceWithVAT(int $priceWithVAT): void
    {
        $this->priceWithVAT = $priceWithVAT;
    }

    public function getDeliveryCost(): int
    {
        return $this->deliveryCost;
    }

    public function setDeliveryCost(int $deliveryCost): void
    {
        $this->deliveryCost = $deliveryCost;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setMetaKeyword(?string $metaKeyword): void
    {
        $this->metaKeyword = $metaKeyword;
    }

    public function getMetaKeyword(): ?string
    {
        return $this->metaKeyword;
    }

    public function setMetaDescription(?string $metaDescription): void
    {
        $this->metaDescription = $metaDescription;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setNote(?string $note)
    {
        $this->note = $note;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCurrency(Currency $currency): void
    {
        $this->currency = $currency;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getImages()
    {
        $images = $this->images->toArray();

        usort($images, function($a, $b) {
            return strcmp($a->getPosition(), $b->getPosition());
        });

        return $images;
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

    public function getComments()
    {
        return $this->comments;
    }

    public function setComments($comments)
    {
        if (count($comments) > 0) {
            foreach ($comments as $comment) {
                $this->addComment($comment);
            }
        }
    }

    /**
     * @param Comment $comment
     */
    public function addComment(Comment $comment)
    {
        $comment->setProduct($this);
        $this->comments[] = $comment;
    }

    /**
     * @param Comment $comment
     */
    public function removeComment(Comment $comment)
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
        }
    }

    public function getCharacteristics()
    {
        return $this->characteristics;
    }

    public function setCharacteristics($characteristics)
    {
        if (count($characteristics) > 0) {
            foreach ($characteristics as $characteristic) {
                $this->addCharacteristic($characteristic);
            }
        }
    }

    /**
     * @param ProductCharacteristic $characteristic
     */
    public function addCharacteristic(ProductCharacteristic $characteristic)
    {
        $characteristic->setProduct($this);
        $this->characteristics[] = $characteristic;
    }

    /**
     * @param ProductCharacteristic $characteristic
     */
    public function removeCharacteristic(ProductCharacteristic $characteristic)
    {
        if ($this->characteristics->contains($characteristic)) {
            $this->characteristics->removeElement($characteristic);
        }
    }

    /**
     * @param ProductFilterAttribute $filterAttribute
     */
    public function addFilterAttribute(ProductFilterAttribute $filterAttribute)
    {
        if ($this->filterAttributes->contains($filterAttribute)) {
            return;
        }

        $filterAttribute->setProduct($this);
        $this->filterAttributes->add($filterAttribute);
    }

    /**
     * @param ProductFilterAttribute $filterAttribute
     */
    public function removeFilterAttribute(ProductFilterAttribute $filterAttribute)
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

    public function setProductCode(?string $productCode): void
    {
        $this->productCode = $productCode;
    }

    public function getProductCode(): ?string
    {
        return $this->productCode;
    }

    public function getPriceWithoutVAT()
    {
        return $this->priceWithVAT / 1.23;
    }

    public function getPurchasePriceUAH()
    {
        return round($this->getPriceWithoutVAT() * $this->currency->getExchangeRate());
    }

    public function getTotalPrice()
    {
        return round($this->getPurchasePriceUAH() + $this->deliveryCost);
    }

    public function getMarge()
    {
        return round($this->price - $this->getTotalPrice());
    }

    public function getMargePercentage()
    {
        return round(($this->getMarge() * 100) / $this->price);
    }

    public function getRatings()
    {
        return $this->ratings;
    }

    public function setRatings($ratings)
    {
        if (count($ratings) > 0) {
            foreach ($ratings as $rating) {
                $this->addRating($rating);
            }
        }
    }

    /**
     * @param ProductRating $rating
     */
    public function addRating(ProductRating $rating)
    {
        $rating->setProduct($this);
        $this->ratings[] = $rating;
    }

    /**
     * @param ProductRating $rating
     */
    public function removeRating(ProductRating $rating)
    {
        if ($this->ratings->contains($rating)) {
            $this->ratings->removeElement($rating);
        }
    }

    public function getAverageRating()
    {
        $total = 0;
        $count = 0;
        foreach ($this->ratings as $rating) {
            $total += $rating->getValue();
            $count++;
        }

        return $count > 0 ? $total/$count : 0;
    }

    public function __toString():string
    {
        return '' . $this->title;
    }
}
