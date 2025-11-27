<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use DateTime;
use App\Traits\DateStorageTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

#[ORM\Table('category', indexes: [
    new ORM\Index(columns: ['title']),
    new ORM\Index(columns: ['status']),
    new ORM\Index(columns: ['position']),
    new ORM\Index(columns: ['created_at']),
    new ORM\Index(columns: ['updated_at'])
 ])]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class Category
{
    use DateStorageTrait;

    public const ACTIVE = 'ACTIVE';
    public const DISABLED = 'DISABLED';

    public const STATUSES = [
        'Активний'     => self::ACTIVE,
        'Заблокований' => self::DISABLED
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $id;

    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: Category::class)]
    private ?Category $parent = null;

    #[ORM\Column(type: 'string')]
    private string $title = "";

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $image = "";

    #[ORM\Column(type: 'string')]
    private ?string $metaKeyword = "";

    #[ORM\Column(type: 'string')]
    private ?string $metaDescription = "";

    #[ORM\Column(type: 'string')]
    private string $slug = "";

    #[ORM\Column(type: 'string')]
    private string $status = '';

    #[ORM\Column(type: 'integer', nullable: true, options: ['unsigned' => true])]
    private ?int $position = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $showInHeader = false;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $hotlineCategory = '';

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $rozetkaCategory = '';

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $promCategoryLink = '';

    #[ORM\Column(type: 'boolean')]
    private bool $showInEkatalogFeed = true;

    #[ORM\Column(type: 'boolean')]
    private bool $showInPromFeed = true;

    #[ORM\Column(type: 'boolean')]
    private bool $showInRozetkaFeed = true;

    #[ORM\Column(type: 'boolean')]
    private bool $showInHotlineFeed = true;

    /** @var ArrayCollection<int, Product>|PersistentCollection<int, Product> $products */
    #[ORM\OneToMany(
        targetEntity: Product::class,
        mappedBy: 'category',
        cascade: ['all'],
        fetch: 'EAGER',
        orphanRemoval: true
    )]
    private ArrayCollection|PersistentCollection $products;

    /** @var ArrayCollection<int, CategoryFeedPrice>|PersistentCollection<int, CategoryFeedPrice> $feedPrices */
    #[ORM\OneToMany(
        targetEntity: CategoryFeedPrice::class,
        mappedBy: 'category',
        cascade: ['all'],
        fetch: 'EAGER',
        orphanRemoval: true
    )]
    private ArrayCollection|PersistentCollection $feedPrices;

    #[ORM\Column(type: 'datetime')]
    public DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    public DateTime $updatedAt;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->feedPrices = new ArrayCollection();
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setParent(?Category $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?Category
    {
        return $this->parent;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function getImage(): ?string
    {
        return $this->image;
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

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setShowInHeader(bool $showInHeader): void
    {
        $this->showInHeader = $showInHeader;
    }

    public function getShowInHeader(): bool
    {
        return $this->showInHeader;
    }

    public function setHotlineCategory(?string $hotlineCategory): void
    {
        $this->hotlineCategory = $hotlineCategory;
    }

    public function getRozetkaCategory(): ?string
    {
        return $this->rozetkaCategory;
    }

    public function setRozetkaCategory(?string $rozetkaCategory): void
    {
        $this->rozetkaCategory = $rozetkaCategory;
    }

    public function getHotlineCategory(): ?string
    {
        return $this->hotlineCategory;
    }

    public function setPromCategoryLink(?string $promCategoryLink): void
    {
        $this->promCategoryLink = $promCategoryLink;
    }

    public function getPromCategoryLink(): ?string
    {
        return $this->promCategoryLink;
    }

    public function setShowInEkatalogFeed(bool $showInEkatalogFeed): void
    {
        $this->showInEkatalogFeed = $showInEkatalogFeed;
    }

    public function getShowInEkatalogFeed(): bool
    {
        return $this->showInEkatalogFeed;
    }

    public function setShowInPromFeed(bool $showInPromFeed): void
    {
        $this->showInPromFeed = $showInPromFeed;
    }

    public function getShowInPromFeed(): bool
    {
        return $this->showInPromFeed;
    }

    public function setShowInRozetkaFeed(bool $showInRozetkaFeed): void
    {
        $this->showInRozetkaFeed = $showInRozetkaFeed;
    }

    public function getShowInRozetkaFeed(): bool
    {
        return $this->showInRozetkaFeed;
    }

    public function setShowInHotlineFeed(bool $showInHotlineFeed): void
    {
        $this->showInHotlineFeed = $showInHotlineFeed;
    }

    public function getShowInHotlineFeed(): bool
    {
        return $this->showInHotlineFeed;
    }

    public function addProduct(Product $product): void
    {
        $product->setCategory($this);
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
        }
    }

    public function removeProduct(Product $product): bool
    {
        return $this->products->removeElement($product);
    }

    /**
     * @return ArrayCollection<int, Product>|PersistentCollection<int, Product>
     */
    public function getProducts(): ArrayCollection|PersistentCollection
    {
        return $this->products;
    }

    public function addFeedPrice(CategoryFeedPrice $feedPrice): void
    {
        $feedPrice->setCategory($this);
        if (!$this->feedPrices->contains($feedPrice)) {
            $this->feedPrices[] = $feedPrice;
        }
    }

    public function removeFeedPrice(CategoryFeedPrice $feedPrice): bool
    {
        return $this->feedPrices->removeElement($feedPrice);
    }

    /**
     * @return ArrayCollection<int, CategoryFeedPrice>|PersistentCollection<int, CategoryFeedPrice>
     */
    public function getFeedPrices(): ArrayCollection|PersistentCollection
    {
        return $this->feedPrices;
    }

    /**
     * @return ArrayCollection<int, Product>|PersistentCollection<int, Product>
     */
    public function getActiveProducts(): ArrayCollection|PersistentCollection
    {
        return $this->products->filter(function (Product $product) {
            return $product->getStatus() === Product::STATUS_ACTIVE;
        });
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
        return $this->title;
    }
}
