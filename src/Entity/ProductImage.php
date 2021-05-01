<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Table("product_image", indexes={
 *     @Index(columns={"product_id", "position"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\ProductImageRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ProductImage
{
    use DateStorageTrait;

    /**
     * @var integer
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    private $imageUrl = "";

    /**
     * @var integer
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $position = 0;

    /**
     * @var Product
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @ORM\ManyToOne(targetEntity="App\Entity\Product", inversedBy="images")
     */
    private $product;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * @param Product|null $product
     */
    public function setProduct(?Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return string
     */
    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    /**
     * @param string $imageUrl
     */
    public function setImageUrl(string $imageUrl)
    {
        $this->imageUrl = $imageUrl;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition(int $position)
    {
        $this->position = $position;
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