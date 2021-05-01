<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Table("filter_parameter_value", indexes={
 *     @Index(columns={"value"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\FilterParameterValueRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class FilterParameterValue
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
    private $value;

    /**
     * @var FilterParameter
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     * @ORM\ManyToOne(targetEntity="App\Entity\FilterParameter")
     */
    private $filterParameter;

    /**
     * @var Product|null
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
     * @param ?FilterParameter $filterParameter
     */
    public function setFilterParameter(?FilterParameter $filterParameter)
    {
        $this->filterParameter = $filterParameter;
    }

    /**
     * @return FilterParameter|null
     */
    public function getFilterParameter(): ?FilterParameter
    {
        return $this->filterParameter;
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
}
