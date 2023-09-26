<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user", indexes={
 *     @Index(columns={"email"}),
 *     @Index(columns={"surname"}),
 *     @Index(columns={"phone"}),
 * })
 */
class User
{
    use DateStorageTrait;

    const MONTH_NAMES = [
        '01' => 'січня',
        '02' => 'лютого',
        '03' => 'березня',
        '04' => 'квітня',
        '05' => 'травня',
        '06' => 'червня',
        '07' => 'липня',
        '08' => 'серпня',
        '09' => 'вересня',
        '10' => 'жовтня',
        '11' => 'листопада',
        '12' => 'грудня',
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $surname;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $phone;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $password;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $address;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $novaPoshtaCity;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $novaPoshtaOffice;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private bool $confirmed = false;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $hash;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Order", mappedBy="user", cascade={"all"}, orphanRemoval=true, fetch="EAGER")
     */
    private $orders;

    /**
     * @ORM\Column(type="datetime")
     */
    public DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    public DateTime $updatedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    public ?DateTime $expiredAt;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): void
    {
        $this->surname = $surname;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getNovaPoshtaCity(): ?string
    {
        return $this->novaPoshtaCity;
    }

    public function setNovaPoshtaCity(?string $novaPoshtaCity): void
    {
        $this->novaPoshtaCity = $novaPoshtaCity;
    }

    public function getNovaPoshtaOffice(): ?string
    {
        return $this->novaPoshtaOffice;
    }

    public function setNovaPoshtaOffice(?string $novaPoshtaOffice): void
    {
        $this->novaPoshtaOffice = $novaPoshtaOffice;
    }

    public function getConfirmed(): bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(bool $confirmed): void
    {
        $this->confirmed = $confirmed;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(?string $hash): void
    {
        $this->hash = $hash;
    }

    public function getOrders()
    {
        return $this->orders;
    }

    public function addOrder(Order $order)
    {
        if (!$this->orders->contains($order)) {
            $order->setUser($this);
            $this->orders[] = $order;
        }
    }

    public function removeOrder(Order $order)
    {
        if ($this->orders->contains($order)) {
            $this->orders->removeElement($order);
        }
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

    public function getExpiredAt(): DateTime
    {
        return $this->expiredAt;
    }

    public function setExpiredAt(DateTime $expiredAt): void
    {
        $this->expiredAt = $expiredAt;
    }
}
