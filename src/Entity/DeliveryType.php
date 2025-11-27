<?php

namespace App\Entity;

use App\Repository\DeliveryTypeRepository;
use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

#[ORM\Table("delivery_type", indexes: [
    new ORM\Index(columns: ["title"]),
])]
#[ORM\Entity(repositoryClass: DeliveryTypeRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class DeliveryType
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\Column(type: "string")]
    private string $title = "";

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $cost = 0;

    #[ORM\Column(type: "boolean")]
    private bool $enabled = true;

    #[ORM\Column(type: "boolean")]
    private bool $needAddressField = true;

    #[ORM\Column(type: "boolean")]
    private bool $isNovaPoshta = false;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $address = "";

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $icon = "";

    /** @var ArrayCollection<int, PaymentType>|PersistentCollection<int, PaymentType> $paymentTypes */
    #[ORM\ManyToMany(targetEntity: PaymentType::class)]
    #[ORM\JoinTable(
        name: 'delivery_payment',
        joinColumns: [
            new ORM\JoinColumn(
                name: 'delivery_type_id',
                referencedColumnName: 'id'
            )
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(
                name: 'payment_type_id',
                referencedColumnName: 'id'
            )
        ]
    )]
    private ArrayCollection|PersistentCollection $paymentTypes;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $priority = 0;

    #[ORM\Column(type: "datetime")]
    private DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    private DateTime $updatedAt;

    public function __construct()
    {
        $this->paymentTypes = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setCost(?int $cost): void
    {
        $this->cost = $cost;
    }

    public function getCost(): ?int
    {
        return $this->cost;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setNeedAddressField(bool $needAddressField): void
    {
        $this->needAddressField = $needAddressField;
    }

    public function getNeedAddressField(): bool
    {
        return $this->needAddressField;
    }

    public function setIsNovaPoshta(bool $isNovaPoshta): void
    {
        $this->isNovaPoshta = $isNovaPoshta;
    }

    public function getIsNovaPoshta(): bool
    {
        return $this->isNovaPoshta;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
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

    public function addPaymentType(PaymentType $paymentType): void
    {
        if (!$this->paymentTypes->contains($paymentType)) {
            $this->paymentTypes[] = $paymentType;
        }
    }

    public function removePaymentType(PaymentType $paymentType): bool
    {
        return $this->paymentTypes->removeElement($paymentType);
    }

    /**
     * @return ArrayCollection<int, PaymentType>|PersistentCollection<int, PaymentType>
     */
    public function getPaymentTypes(): ArrayCollection|PersistentCollection
    {
        return $this->paymentTypes;
    }

    public function setPriority(?int $priority): void
    {
        $this->priority = $priority;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
