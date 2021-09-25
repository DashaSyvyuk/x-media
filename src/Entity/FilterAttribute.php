<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\ManyToMany;

/**
 * @ORM\Table("filter_attributes", indexes={
 *     @Index(columns={"value"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\FilterAttributeRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class FilterAttribute
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
    private $value = "";

    /**
     * @var Filter
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     * @ORM\ManyToOne(targetEntity="App\Entity\Filter")
     */
    private $filter;

    /**
     * @ManyToMany(targetEntity="App\Entity\Product")
     * @ORM\JoinTable(name="product_filter_attribute",
     *      joinColumns={@ORM\JoinColumn(name="filter_attribute_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")}
     *      )
     */
    private $products;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private int $priority = 0;

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

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param ?Filter $filter
     */
    public function setFilter(?Filter $filter)
    {
        $this->filter = $filter;
    }

    /**
     * @return Filter|null
     */
    public function getFilter(): ?Filter
    {
        return $this->filter;
    }

    /**
     * @param Product $product
     */
    public function addProduct(Product $product)
    {
        if ($this->products->contains($product)) {
            return;
        }

        $this->products->add($product);
    }

    /**
     * @param Product $product
     */
    public function removeProduct(Product $product)
    {
        if (!$this->products->contains($product)) {
            return;
        }

        $this->products->removeElement($product);
    }

    public function getProducts()
    {
        return $this->products;
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

    public function __toString(): string
    {
        return '' . $this->value;
    }
}
