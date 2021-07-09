<?php

namespace App\Entity;

use \DateTime;
use App\Traits\DateStorageTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("orders")
 * @ORM\Entity(repositoryClass="App\Repository\OrderRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Order
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
    private string $name = "";

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private string $surname = "";

    /**
     * @ORM\Column(type="text")
     */
    private string $address = "";

    /**
     * @ORM\Column(type="string")
     */
    private string $phone = "";

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private string $email = "";

    /**
     * @ORM\Column(type="string")
     */
    private string $paytype = "";

    /**
     * @ORM\Column(type="string")
     */
    private string $status = "";

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private string $comment = "";

    /**
     * @ORM\Column(type="integer")
     */
    private int $total = 0;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrderItem", mappedBy="order", cascade={"all"}, orphanRemoval=true, fetch="EAGER")
     */
    private $items;

    /**
     * @ORM\Column(type="datetime")
     */
    public DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    public DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setSurname(string $surname): void
    {
        $this->surname = $surname;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setPaytype(string $paytype): void
    {
        $this->paytype = $paytype;
    }

    public function getPaytype(): string
    {
        return $this->paytype;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setTotal(int $total)
    {
        $this->total = $total;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param OrderItem $item
     */
    public function addItem(OrderItem $item)
    {
        $item->setOrder($this);
        $this->items[] = $item;
    }

    /**
     * @param OrderItem $item
     */
    public function removeItem(OrderItem $item)
    {
        if ($this->items->contains($item)) {
            $this->items->removeElement($item);
        }
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

    public function __toString():string
    {
        return '' . $this->name;
    }
}
