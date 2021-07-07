<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

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
     * @var Product
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     * @ORM\ManyToOne(targetEntity="App\Entity\Product")
     */
    private $product;

    /**
     * @ORM\Column(type="datetime")
     */
    public $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    public $updatedAt;

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
     * @param ?Product $product
     */
    public function setProduct(?Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        return $this->product;
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
