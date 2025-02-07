<?php

namespace App\Entity;

use DateTime;
use App\Traits\DateStorageTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table("rozetka_product")
 * @ORM\Entity(repositoryClass="App\Repository\RozetkaProductRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class RozetkaProduct
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
    private string $title = "";

    /**
     * @ORM\Column(type="integer")
     */
    private int $stockQuantity = 0;

    /**
     * @ORM\Column(type="string")
     */
    private string $article = "";

    /**
     * @ORM\Column(type="string")
     */
    private string $series = "";

    /**
     * @ORM\Column(type="text")
     */
    private string $description = "";

    /**
     * @ORM\Column(type="integer")
     * @Assert\GreaterThanOrEqual(value="1", message="Too low value")
     */
    private int $price = 0;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ProductRozetkaCharacteristicValue", mappedBy="rozetkaProduct", cascade={"all"}, orphanRemoval=true)
     */
    private $values;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Product", mappedBy="rozetkaProduct")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    private Product $product;

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
        $this->values = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(int $stockQuantity): void
    {
        $this->stockQuantity = $stockQuantity;
    }

    public function getArticle(): string
    {
        return $this->article;
    }

    public function setArticle(string $article): void
    {
        $this->article = $article;
    }

    public function getSeries(): string
    {
        return $this->series;
    }

    public function setSeries(string $series): void
    {
        $this->series = $series;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }

    public function getValues()
    {
        return $this->values;
    }

    public function setValues($values): void
    {
        if (count($values) > 0) {
            foreach ($values as $value) {
                $this->addValue($value);
            }
        }
    }

    /**
     * @param ProductRozetkaCharacteristicValue $value
     */
    public function addValue(ProductRozetkaCharacteristicValue $value): void
    {
        $value->setRozetkaProduct($this);
        $this->values[] = $value;
    }

    /**
     * @param ProductRozetkaCharacteristicValue $value
     */
    public function removeValue(ProductRozetkaCharacteristicValue $value): void
    {
        if ($this->values->contains($value)) {
            $this->values->removeElement($value);
        }
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): void
    {
        $this->product = $product;
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
