<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use App\Validator\OrderStatus;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Table("orders", indexes={
 *     @Index(columns={"order_number"}),
 *     @Index(columns={"surname"}),
 *     @Index(columns={"phone"}),
 *     @Index(columns={"email"}),
 *     @Index(columns={"status"}),
 *     @Index(columns={"created_at"}),
 * })
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
        self::NEW => [
            'id'    => 1,
            'color' => '#119E00',
            'title' => 'Нове'
        ],
        self::NOT_APPROVED => [
            'id'    => 1,
            'color' => '#119E00',
            'title' => 'Нове'
        ],
        self::APPROVED => [
            'id'    => 2,
            'color' => '#FF8C00',
            'title' => 'В процесі'
        ],
        self::PACKING                => [
            'id'    => 2,
            'color' => '#FF8C00',
            'title' => 'В процесі'
        ],
        self::NOVA_POSHTA_DELIVERING => [
            'id'    => 3,
            'color' => '#0000FF',
            'title' => 'Відправлено'
        ],
        self::COURIER_DELIVERING     => [
            'id'    => 3,
            'color' => '#0000FF',
            'title' => 'Відправлено'
        ],
        self::COMPLETED              => [
            'id'    => 4,
            'color' => '#000000',
            'title' => 'Доставлено'
        ],
        self::CANCELED_NOT_CONFIRMED => [
            'id'    => 5,
            'color' => '#808080',
            'title' => 'Відмінено'
        ],
        self::CANCELED_NO_PRODUCT    => [
            'id'    => 5,
            'color' => '#808080',
            'title' => 'Відмінено'
        ],
        self::CANCELED_NOT_PICKED_UP => [
            'id'    => 5,
            'color' => '#808080',
            'title' => 'Відмінено'
        ],
    ];

    const LABEL_XMEDIA = 'x-media';
    const LABEL_PROM = 'prom';
    const LABEL_OLX = 'olx';
    const LABEL_JONNY = 'Jonny';
    const LABELS = [
        self::LABEL_XMEDIA => self::LABEL_XMEDIA,
        self::LABEL_PROM   => self::LABEL_PROM,
        self::LABEL_OLX    => self::LABEL_OLX,
        self::LABEL_JONNY  => self::LABEL_JONNY,
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
    private string $orderNumber = "";

    /**
     * @ORM\Column(type="string")
     */
    private string $name = "";

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $surname = "";

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
    private ?string $email = null;

    /**
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     * @ORM\ManyToOne(targetEntity="App\Entity\PaymentType")
     */
    private ?PaymentType $paytype;

    /**
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     * @ORM\ManyToOne(targetEntity="App\Entity\DeliveryType")
     */
    private ?DeliveryType $deltype;

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
     * @ORM\OneToMany(targetEntity="App\Entity\OrderItem", mappedBy="order", cascade={"all"}, orphanRemoval=true)
     */
    private $items;

    /**
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private ?User $user;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $sendNotification = false;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     */
    private ?array $labels = [];

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

    public function setSurname(?string $surname): void
    {
        $this->surname = $surname;
    }

    public function getSurname(): ?string
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

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setPaytype(?PaymentType $paytype): void
    {
        $this->paytype = $paytype;
    }

    public function getPaytype(): ?PaymentType
    {
        return $this->paytype;
    }

    public function setDeltype(?DeliveryType $deltype): void
    {
        $this->deltype = $deltype;
    }

    public function getDeltype(): ?DeliveryType
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

    public function setTotal(int $total): void
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
    public function addItem(OrderItem $item): void
    {
        if (!$this->items->contains($item)) {
            $item->setOrder($this);
            $this->items[] = $item;
        }
    }

    /**
     * @param OrderItem $item
     */
    public function removeItem(OrderItem $item): void
    {
        if ($this->items->contains($item)) {
            $this->items->removeElement($item);
        }
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getSendNotification(): bool
    {
        return $this->sendNotification;
    }

    public function setSendNotification(bool $sendNotification): void
    {
        $this->sendNotification = $sendNotification;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setLabels(?array $labels): void
    {
        $this->labels = $labels;
    }

    public function getLabels(): ?array
    {
        return $this->labels;
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
        return $this->name;
    }
}
