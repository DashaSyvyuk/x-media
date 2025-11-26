<?php

namespace App\Entity;

use DateTime;
use App\Traits\DateStorageTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table("circulation_payments")]
#[ORM\Entity(repositoryClass: "App\Repository\CirculationPaymentRepository")]
#[ORM\HasLifecycleCallbacks()]
class CirculationPayment
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $sum = 0;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $note = '';

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $description = '';

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: "App\Entity\Circulation")]
    private Circulation $circulation;

    #[ORM\Column(type: "datetime")]
    private DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    private DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSum(): int
    {
        return $this->sum;
    }

    public function setSum(int $sum): void
    {
        $this->sum = $sum;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCirculation(): Circulation
    {
        return $this->circulation;
    }

    public function setCirculation(Circulation $circulation): void
    {
        $this->circulation = $circulation;
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
        return sprintf('%s __________________ %s', $this->description, $this->createdAt->format('Y-m-d H:i'));
    }
}
