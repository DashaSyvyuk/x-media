<?php

namespace App\Entity;

use Carbon\Carbon;
use DateTime;
use App\Traits\DateStorageTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Symfony\Component\Validator\Constraints as Assert;

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
    const STATUS_ACTIVE = 'Активний';
    const STATUS_BLOCKED = 'Заблокований';

    const AVAILABILITY_AVAILABLE = 'в наявності';
    const AVAILABILITY_TO_ORDER = 'під замовлення (1-3 дні)';
    const AVAILABILITY_PRE_ORDER = 'передзамовлення';

    const AVAILABILITIES = [
        self::AVAILABILITY_AVAILABLE => self::AVAILABILITY_AVAILABLE,
        self::AVAILABILITY_TO_ORDER => self::AVAILABILITY_TO_ORDER,
        self::AVAILABILITY_PRE_ORDER => self::AVAILABILITY_PRE_ORDER,
    ];

    const AVAILABILITY_ICONS = [
        self::AVAILABILITY_AVAILABLE => 'images/available.svg',
        self::AVAILABILITY_PRE_ORDER => 'images/pre_order.svg',
        self::AVAILABILITY_TO_ORDER => 'images/to_order.svg',
    ];

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
     * @ORM\Column(type="string")
     */
    private string $availability = "";

    /**
     * @ORM\Column(type="integer")
     * @Assert\GreaterThanOrEqual(value="1", message="Too low value")
     */
    private int $price = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\GreaterThan(propertyPath="price", message="Too low value")
     */
    private ?int $crossedOutPrice = 0;

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
    private ?Category $category = null;

    /**
     * @ORM\OneToMany(targetEntity="ProductImage", mappedBy="product", cascade={"all"}, orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     */
    private $images;

    /**
     * @ORM\OneToMany(targetEntity="ProductCharacteristic", mappedBy="product", cascade={"all"}, orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     */
    private $characteristics;

    /**
     * @ORM\OneToMany(targetEntity="ProductFilterAttribute", mappedBy="product", cascade={"all"}, orphanRemoval=true)
     */
    private $filterAttributes;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\RozetkaProduct", mappedBy="product", cascade={"remove"}, orphanRemoval=true)
     */
    private $rozetka;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="product", cascade={"all"}, orphanRemoval=true)
     */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity="ProductRating", mappedBy="product", cascade={"all"}, orphanRemoval=true)
     */
    private $ratings;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\PromotionProduct", mappedBy="product", cascade={"all"}, orphanRemoval=true)
     */
    private $promotionProducts;

    /**
     * @ORM\Column(type="string")
     */
    private string $productCode = "";

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $productCode2 = "";

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $olx;

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
        $this->promotionProducts = new ArrayCollection();
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

    public function getAvailability(): string
    {
        return $this->availability;
    }

    public function setAvailability(string $availability): void
    {
        $this->availability = $availability;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }

    public function getCrossedOutPrice(): ?int
    {
        return $this->crossedOutPrice;
    }

    public function setCrossedOutPrice(?int $crossedOutPrice): void
    {
        $this->crossedOutPrice = $crossedOutPrice;
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
        $this->metaKeyword = $this->title;
    }

    public function getMetaKeyword(): ?string
    {
        return $this->metaKeyword;
    }

    public function setMetaDescription(?string $metaDescription): void
    {
        $this->metaDescription = $this->title;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setNote(?string $note): void
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function getImages()
    {
        return $this->images;
    }

    public function setImages($images): void
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
    public function addImage(ProductImage $image): void
    {
        $image->setProduct($this);
        $this->images[] = $image;
    }

    /**
     * @param ProductImage $image
     */
    public function removeImage(ProductImage $image): void
    {
        if ($this->images->contains($image)) {
            $this->images->removeElement($image);
        }
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function getConfirmedComments()
    {
        return $this->comments->filter(fn (Comment $comment) => $comment->getStatus() == Comment::STATUS_CONFIRMED);
    }

    public function setComments($comments): void
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
    public function addComment(Comment $comment): void
    {
        $comment->setProduct($this);
        $this->comments[] = $comment;
    }

    /**
     * @param Comment $comment
     */
    public function removeComment(Comment $comment): void
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
        }
    }

    public function getCharacteristics()
    {
        return $this->characteristics;
    }

    public function setCharacteristics($characteristics): void
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
    public function addCharacteristic(ProductCharacteristic $characteristic): void
    {
        $characteristic->setProduct($this);
        $this->characteristics[] = $characteristic;
    }

    /**
     * @param ProductCharacteristic $characteristic
     */
    public function removeCharacteristic(ProductCharacteristic $characteristic): void
    {
        if ($this->characteristics->contains($characteristic)) {
            $this->characteristics->removeElement($characteristic);
        }
    }

    /**
     * @param ProductFilterAttribute $filterAttribute
     */
    public function addFilterAttribute(ProductFilterAttribute $filterAttribute): void
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
    public function removeFilterAttribute(ProductFilterAttribute $filterAttribute): void
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

    public function setRozetka(?RozetkaProduct $rozetka): void
    {
        $this->rozetka = $rozetka;
    }

    public function getRozetka(): ?RozetkaProduct
    {
        return $this->rozetka;
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

    public function setProductCode(string $productCode): void
    {
        $this->productCode = $productCode;
    }

    public function getProductCode(): string
    {
        return $this->productCode;
    }

    public function setProductCode2(?string $productCode2): void
    {
        $this->productCode2 = $productCode2;
    }

    public function getProductCode2(): ?string
    {
        return $this->productCode2;
    }

    public function setOlx(?string $olx): void
    {
        $this->olx = $olx;
    }

    public function getOlx(): ?string
    {
        return $this->olx;
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

    public function getPromotionProducts()
    {
        return $this->promotionProducts;
    }

    public function addPromotionProduct(PromotionProduct $promotionProduct): void
    {
        if (!$this->promotionProducts->contains($promotionProduct)) {
            $promotionProduct->setProduct($this);
            $this->promotionProducts[] = $promotionProduct;
        }
    }

    public function removePromotionProduct(PromotionProduct $promotionProduct): void
    {
        if ($this->promotionProducts->contains($promotionProduct)) {
            $this->promotionProducts->removeElement($promotionProduct);
        }
    }

    public function getCalculatedCrossedOutPrice(): ?int
    {
        $now = Carbon::now();
        $promotionExists = false;

        foreach ($this->promotionProducts as $product) {
            $promotion = $product->getPromotion();
            if ($promotion->getStatus() === Promotion::ACTIVE &&
                $promotion->getActiveFrom() <= $now && $promotion->getActiveTo() >= $now
            ) {
                $promotionExists = true;
            }
        }

        return $promotionExists ? $this->crossedOutPrice : 0;
    }

    public function __toString(): string
    {
        return $this->id . ' - ' . $this->title;
    }
}
