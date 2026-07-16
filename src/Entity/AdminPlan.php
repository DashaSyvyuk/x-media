<?php

namespace App\Entity;

use App\Repository\AdminPlanRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminPlanRepository::class)]
#[ORM\Table(name: 'admin_plans')]
#[ORM\HasLifecycleCallbacks]
class AdminPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $scheduledDate;

    #[ORM\ManyToOne(targetEntity: AdminUser::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AdminUser $assignee;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $body = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    #[ORM\ManyToOne(targetEntity: AdminUser::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?AdminUser $createdBy = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $now = new \DateTime();
        $this->scheduledDate = $now;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScheduledDate(): \DateTimeInterface
    {
        return $this->scheduledDate;
    }

    public function setScheduledDate(\DateTimeInterface $scheduledDate): self
    {
        $this->scheduledDate = $scheduledDate instanceof \DateTime
            ? $scheduledDate
            : \DateTime::createFromInterface($scheduledDate);

        return $this;
    }

    public function getAssignee(): AdminUser
    {
        return $this->assignee;
    }

    public function setAssignee(AdminUser $assignee): self
    {
        $this->assignee = $assignee;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): self
    {
        if ($completedAt === null) {
            $this->completedAt = null;
        } else {
            $this->completedAt = $completedAt instanceof \DateTime
                ? $completedAt
                : \DateTime::createFromInterface($completedAt);
        }

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->completedAt !== null;
    }

    public function getCreatedBy(): ?AdminUser
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?AdminUser $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    /** @return 'today'|'past'|'future' */
    public function getDayBucket(\DateTimeInterface $today): string
    {
        $date = $this->scheduledDate->format('Y-m-d');
        $todayKey = $today->format('Y-m-d');

        if ($date === $todayKey) {
            return 'today';
        }

        return $date < $todayKey ? 'past' : 'future';
    }
}
