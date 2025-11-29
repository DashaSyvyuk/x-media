<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use App\Traits\DateStorageTrait;
use App\Validator\OrderAddress;
use App\Validator\OrderStatus;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table("orders", indexes: [
    new ORM\Index(columns: ["order_number"]),
    new ORM\Index(columns: ["surname"]),
    new ORM\Index(columns: ["phone"]),
    new ORM\Index(columns: ["email"]),
    new ORM\Index(columns: ["status"]),
    new ORM\Index(columns: ["created_at"]),
])]
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[OrderStatus]
#[OrderAddress]
class Order
{
    use DateStorageTrait;

    public const NEW = 'new';
    public const NOT_APPROVED =  'not_approved';
    public const APPROVED = 'confirmed';
    public const PACKING = 'packing';
    public const NOVA_POSHTA_DELIVERING = 'nova_poshta_delivering';
    public const COURIER_DELIVERING = 'courier_delivering';
    public const COMPLETED = 'completed';
    public const CANCELED_NOT_CONFIRMED = 'canceled_not_confirmed';
    public const CANCELED_NO_PRODUCT = 'canceled_no_product';
    public const CANCELED_NOT_PICKED_UP = 'canceled_not_picked_up';

    public const STATUSES = [
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

    public const GROUPED_STATUSES = [
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
        self::PACKING => [
            'id'    => 2,
            'color' => '#FF8C00',
            'title' => 'В процесі'
        ],
        self::NOVA_POSHTA_DELIVERING => [
            'id'    => 3,
            'color' => '#0000FF',
            'title' => 'Відправлено'
        ],
        self::COURIER_DELIVERING => [
            'id'    => 3,
            'color' => '#0000FF',
            'title' => 'Відправлено'
        ],
        self::COMPLETED => [
            'id'    => 4,
            'color' => '#000000',
            'title' => 'Доставлено'
        ],
        self::CANCELED_NOT_CONFIRMED => [
            'id'    => 5,
            'color' => '#808080',
            'title' => 'Відмінено'
        ],
        self::CANCELED_NO_PRODUCT => [
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

    public const LABEL_XMEDIA = 'x-media';
    public const LABEL_PROM = 'prom';
    public const LABEL_OLX = 'olx';
    public const LABEL_JONNY = 'Jonny';
    public const LABEL_ROZETKA = 'Rozetka';
    public const LABELS = [
        self::LABEL_XMEDIA  => self::LABEL_XMEDIA,
        self::LABEL_PROM    => self::LABEL_PROM,
        self::LABEL_OLX     => self::LABEL_OLX,
        self::LABEL_JONNY   => self::LABEL_JONNY,
        self::LABEL_ROZETKA => self::LABEL_ROZETKA,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id = 0;

    #[ORM\Column(type: "string")]
    private string $orderNumber = "";

    #[ORM\Column(type: "string")]
    #[Assert\NotBlank(message: 'order.field.required')]
    private string $name = "";

    #[ORM\Column(type: "string", nullable: true)]
    #[Assert\NotBlank(message: 'order.field.required')]
    private ?string $surname = "";

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $address = "";

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: NovaPoshtaCity::class)]
    private ?NovaPoshtaCity $novaPoshtaCity = null;

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: NovaPoshtaOffice::class)]
    private ?NovaPoshtaOffice $novaPoshtaOffice = null;

    #[ORM\Column(type: "string")]
    #[Assert\NotBlank(message: 'order.field.required')]
    private string $phone = "";

    #[ORM\Column(type: "string", nullable: true)]
    #[Assert\Email(message: 'order.wrong_format')]
    private ?string $email = null;

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: PaymentType::class)]
    private ?PaymentType $paytype;

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: DeliveryType::class)]
    private ?DeliveryType $deltype;

    #[ORM\Column(type: "string")]
    private string $status = "";

    #[ORM\Column(type: "boolean")]
    private bool $paymentStatus;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $ttn = "";

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $comment = "";

    #[ORM\Column(type: "string")]
    private string $source = 'User Side';

    #[ORM\Column(type: "integer")]
    private int $total = 0;

    /** @var ArrayCollection<int, OrderItem>|PersistentCollection<int, OrderItem> $items */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: "order", cascade: ["all"], orphanRemoval: true)]
    private ArrayCollection|PersistentCollection $items;

    #[ORM\JoinColumn(nullable: true, onDelete: "CASCADE")]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "orders")]
    private ?User $user;

    #[ORM\Column(type: "boolean")]
    private bool $sendNotification = false;

    /** @var array<string>|null $labels */
    #[ORM\Column(type: "simple_array", nullable: true)]
    private ?array $labels = [];

    #[ORM\Column(type: "datetime")]
    public DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    public DateTime $updatedAt;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?int
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

    public function setNovaPoshtaCity(?NovaPoshtaCity $novaPoshtaCity): void
    {
        $this->novaPoshtaCity = $novaPoshtaCity;
    }

    public function getNovaPoshtaCity(): ?NovaPoshtaCity
    {
        return $this->novaPoshtaCity;
    }

    public function setNovaPoshtaOffice(?NovaPoshtaOffice $novaPoshtaOffice): void
    {
        $this->novaPoshtaOffice = $novaPoshtaOffice;
    }

    public function getNovaPoshtaOffice(): ?NovaPoshtaOffice
    {
        return $this->novaPoshtaOffice;
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

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @return ArrayCollection<int, OrderItem>|PersistentCollection<int, OrderItem>
     */
    public function getItems(): ArrayCollection|PersistentCollection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): void
    {
        if (!$this->items->contains($item)) {
            $item->setOrder($this);
            $this->items[] = $item;
        }
    }

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

    /**
     * @param array<string>|null $labels
     */
    public function setLabels(?array $labels): void
    {
        $this->labels = $labels;
    }

    /**
     * @return array<string>|null
     */
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
