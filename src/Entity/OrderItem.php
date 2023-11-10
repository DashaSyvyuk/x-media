<?php

namespace App\Entity;

use \DateTime;
use App\Traits\DateStorageTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("order_item")
 * @ORM\Entity(repositoryClass="App\Repository\OrderItemRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class OrderItem
{
    use DateStorageTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $count = 0;

    /**
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @ORM\ManyToOne(targetEntity="App\Entity\Product")
     */
    private Product $product;

    /**
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     * @ORM\ManyToOne(targetEntity="App\Entity\Order", inversedBy="items")
     */
    private Order $order;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @param Product $product
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * @param Order $order
     */
    public function setOrder(Order $order)
    {
        $this->order = $order;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): void
    {
        $this->count = $count;
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
}
