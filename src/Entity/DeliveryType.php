<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("delivery_type")
 * @ORM\Entity(repositoryClass="App\Repository\DeliveryTypeRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class DeliveryType
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
    private string $title = "";

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $cost = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $enabled = true;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $icon = "";

    /**
     * @ORM\ManyToMany(targetEntity="PaymentType")
     * @ORM\JoinTable(name="delivery_payment",
     *      joinColumns={@ORM\JoinColumn(name="delivery_type_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="payment_type_id", referencedColumnName="id")}
     * )
     */
    private $paymentTypes;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $updatedAt;

    public function __construct()
    {
        $this->paymentTypes = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): int
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

    /**
     * @param PaymentType $paymentType
     */
    public function addPaymentType(PaymentType $paymentType): void
    {
        if (!$this->paymentTypes->contains($paymentType)) {
            $this->paymentTypes[] = $paymentType;
        }
    }

    /**
     * @param PaymentType $paymentType
     * @return bool
     */
    public function removePaymentType(PaymentType $paymentType): bool
    {
        return $this->paymentTypes->removeElement($paymentType);
    }

    public function getPaymentTypes()
    {
        return $this->paymentTypes;
    }

    public function __toString()
    {
        return $this->title;
    }
}
