<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use App\Validator\OrderStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("orders")
 * @ORM\Entity(repositoryClass="App\Repository\OrderRepository")
 * @ORM\HasLifecycleCallbacks()
 * @OrderStatus()
 */
class Order
{
    use DateStorageTrait;

    const NEW = 'new';
    const NOT_APPROVED =  'not_approved';
    const APPROVED = 'confirmed';
    const PACKING = 'packing';
    const NOVA_POSHTA_DELIVERING = 'nova_poshta_delivering';
    const COURIER_DELIVERING = 'courier_delivering';
    const COMPLETED = 'completed';
    const CANCELED_NOT_CONFIRMED = 'canceled_not_confirmed';
    const CANCELED_NO_PRODUCT = 'canceled_no_product';
    const CANCELED_NOT_PICKED_UP = 'canceled_not_picked_up';

    const STATUSES = [
        self::NEW                    => 'Нове замовлення',
        self::NOT_APPROVED           => 'Очікує на підтвердження',
        self::APPROVED               => 'Підтверджено. В дорозі від постачальника',
        self::PACKING                => 'Готується до відправлення',
        self::NOVA_POSHTA_DELIVERING => 'Відправлено новою поштою',
        self::COURIER_DELIVERING     => 'Відправлено кур\'єром',
        self::COMPLETED              => 'Доставлено',
        self::CANCELED_NOT_CONFIRMED => 'Відмінено. Не підтверджене',
        self::CANCELED_NO_PRODUCT    => 'Відмінено. Немає продукту',
        self::CANCELED_NOT_PICKED_UP => 'Відмінено. Не відібране',
    ];

    const GROUPED_STATUSES = [
        self::NEW                    => 1,
        self::NOT_APPROVED           => 1,
        self::APPROVED               => 2,
        self::PACKING                => 2,
        self::NOVA_POSHTA_DELIVERING => 3,
        self::COURIER_DELIVERING     => 3,
        self::COMPLETED              => 4,
        self::CANCELED_NOT_CONFIRMED => 5,
        self::CANCELED_NO_PRODUCT    => 5,
        self::CANCELED_NOT_PICKED_UP => 5,
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /**
     * @ORM\Column(type="string")
     */
    private string $orderNumber = "";

    /**
     * @ORM\Column(type="string")
     */
    private string $name = "";

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private string $surname = "";

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $address = "";

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
    private string $deltype = "";

    /**
     * @ORM\Column(type="string")
     */
    private string $status = "";

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $paymentStatus;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $ttn = "";

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $comment = "";

    /**
     * @ORM\Column(type="integer")
     */
    private int $total = 0;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrderItem", mappedBy="order", cascade={"all"}, orphanRemoval=true, fetch="EAGER")
     */
    private $items;

    /**
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private ?User $user;

    /**
     * @ORM\Column(type="datetime")
     */
    public $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    public $updatedAt;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setOrderNumber(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
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

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getAddress(): ?string
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

    public function setDeltype(string $deltype): void
    {
        $this->deltype = $deltype;
    }

    public function getDeltype(): string
    {
        return $this->deltype;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getPaymentStatus(): bool
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(bool $paymentStatus): void
    {
        $this->paymentStatus = $paymentStatus;
    }

    public function getTtn(): ?string
    {
        return $this->ttn;
    }

    public function setTtn(?string $ttn): void
    {
        $this->ttn = $ttn;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    public function getComment(): ?string
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

    public function setUser(?User $user)
    {
        $this->user = $user;
    }

    public function getUser(): ?User
    {
        return $this->user;
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

    public function __toString():string
    {
        return '' . $this->name;
    }
}
