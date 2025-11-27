<?php

namespace App\Entity;

use App\Repository\DebtorRepository;
use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

#[ORM\Table("debtors")]
#[ORM\Entity(repositoryClass: DebtorRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class Debtor
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private ?int $id = 0;

    #[ORM\Column(type: "string")]
    private string $name = "";

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: Currency::class)]
    private Currency $currency;

    /** @var ArrayCollection<int, DebtorPayment>|PersistentCollection<int, DebtorPayment> $payments */
    #[ORM\OneToMany(targetEntity: DebtorPayment::class, mappedBy: "debtor", cascade: ["all"], orphanRemoval: true)]
    private ArrayCollection|PersistentCollection $payments;

    public ?string $total = '';

    #[ORM\Column(type: "datetime")]
    public DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    public DateTime $updatedAt;

    #[ORM\Column(type: "boolean")]
    private bool $active = true;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?int
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

    public function setCurrency(Currency $currency): void
    {
        $this->currency = $currency;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * @return ArrayCollection<int, DebtorPayment>|PersistentCollection<int, DebtorPayment>
     */
    public function getPayments(): ArrayCollection|PersistentCollection
    {
        return $this->payments;
    }

    public function addPayment(DebtorPayment $payment): void
    {
        if (!$this->payments->contains($payment)) {
            $payment->setDebtor($this);
            $this->payments[] = $payment;
        }
    }

    public function removePayment(DebtorPayment $payment): void
    {
        if ($this->payments->contains($payment)) {
            $this->payments->removeElement($payment);
        }
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
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
        return $this->name;
    }
}
