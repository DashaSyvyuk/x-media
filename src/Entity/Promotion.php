<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("promotions")
 * @ORM\Entity(repositoryClass="App\Repository\PromotionRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Promotion
{
    use DateStorageTrait;

    const ACTIVE = 'active';
    const BLOCKED =  'blocked';

    const STATUSES = [
        self::ACTIVE  => 'Активна',
        self::BLOCKED => 'Заблокована',
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id = 0;

    /**
     * @ORM\Column(type="string")
     */
    private string $title = "";

    /**
     * @ORM\Column(type="string")
     */
    private string $slug = "";

    /**
     * @ORM\Column(type="string")
     */
    private string $description = "";

    /**
     * @ORM\Column(type="string")
     */
    private string $status = "";

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $activeFrom;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $activeTo;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\PromotionProduct", mappedBy="promotion", cascade={"all"}, orphanRemoval=true)
     */
    private $products;

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
        $this->products = new ArrayCollection();
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

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setActiveFrom(DateTime $activeFrom): void
    {
        $this->activeFrom = $activeFrom;
    }

    public function getActiveFrom(): DateTime
    {
        return $this->activeFrom;
    }

    public function setActiveTo(DateTime $activeTo): void
    {
        $this->activeTo = $activeTo;
    }

    public function getActiveTo(): DateTime
    {
        return $this->activeTo;
    }

    public function getProducts()
    {
        return $this->products;
    }

    public function addProduct(PromotionProduct $product): void
    {
        if (!$this->products->contains($product)) {
            $product->setPromotion($this);
            $this->products[] = $product;
        }
    }

    public function removeProduct(PromotionProduct $product): void
    {
        if ($this->products->contains($product)) {
            $this->products->removeElement($product);
        }
    }

    public function getActiveProducts(int $maxCount = 10): array
    {
        $result = [];
        foreach ($this->products as $key => $promotionProduct) {
            if ($promotionProduct->getProduct()->getStatus() === Product::STATUS_ACTIVE && $key < $maxCount) {
                $result[] = $promotionProduct;
            }
        }

        return $result;
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
