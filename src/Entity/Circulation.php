<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("circulations")
 * @ORM\Entity(repositoryClass="App\Repository\CirculationRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Circulation
{
    use DateStorageTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private ?int $id = 0;

    /**
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     * @ORM\ManyToOne(targetEntity="App\Entity\AdminUser")
     */
    private ?AdminUser $adminUser = null;

    /**
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     * @ORM\ManyToOne(targetEntity="App\Entity\Currency")
     */
    private Currency $currency;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CirculationPayment", mappedBy="circulation", cascade={"all"}, orphanRemoval=true)
     */
    private $payments;

    public ?string $total = '';

    /**
     * @ORM\Column(type="datetime")
     */
    public DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    public DateTime $updatedAt;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private bool $active = true;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setAdminUser(?AdminUser $adminUser): void
    {
        $this->adminUser = $adminUser;
    }

    public function getAdminUser(): ?AdminUser
    {
        return $this->adminUser;
    }

    public function setCurrency(Currency $currency): void
    {
        $this->currency = $currency;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getPayments()
    {
        return $this->payments;
    }

    public function addPayment(CirculationPayment $payment): void
    {
        if (!$this->payments->contains($payment)) {
            $payment->setCirculation($this);
            $this->payments[] = $payment;
        }
    }

    public function removePayment(CirculationPayment $payment): void
    {
        if ($this->payments->contains($payment)) {
            $this->payments->removeElement($payment);
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

    public function getTotal(): string
    {
        $total = 0;

        foreach ($this->payments as $payment) {
            $total += $payment->getSum();
        }

        return (string) $total;
    }

    public function __toString(): string
    {
        return $this->adminUser?->getEmail();
    }
}
