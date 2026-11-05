<?php

namespace App\Entity;

use App\Repository\AdminPlanRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminPlanRepository::class)]
#[ORM\Table(name: 'admin_plans')]
#[ORM\HasLifecycleCallbacks]
class AdminPlan
{
    public const STATUS_NEW = 'new';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_NEW         => 'Нове',
        self::STATUS_IN_PROGRESS => 'В роботі',
        self::STATUS_COMPLETED   => 'Завершено',
    ];

    public const STATUS_COLORS = [
        self::STATUS_NEW         => '#2563eb',
        self::STATUS_IN_PROGRESS => '#d97706',
        self::STATUS_COMPLETED   => '#059669',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: AdminPlanBoard::class, inversedBy: 'plans')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AdminPlanBoard $board;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $scheduledDate = null;

    #[ORM\ManyToOne(targetEntity: AdminUser::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?AdminUser $assignee = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $body = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_NEW;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $position = 0;

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
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBoard(): AdminPlanBoard
    {
        return $this->board;
    }

    public function setBoard(AdminPlanBoard $board): self
    {
        $this->board = $board;

        return $this;
    }

    public function getScheduledDate(): ?\DateTimeInterface
    {
        return $this->scheduledDate;
    }

    public function setScheduledDate(?\DateTimeInterface $scheduledDate): self
    {
        if ($scheduledDate === null) {
            $this->scheduledDate = null;

            return $this;
        }

        $date = $scheduledDate instanceof \DateTime
            ? clone $scheduledDate
            : \DateTime::createFromInterface($scheduledDate);
        $date->setTime(0, 0);
        $this->scheduledDate = $date;

        return $this;
    }

    public function getAssignee(): ?AdminUser
    {
        return $this->assignee;
    }

    public function setAssignee(?AdminUser $assignee): self
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? '#64748b';
    }

    public function setStatus(string $status): self
    {
        if (! isset(self::STATUSES[$status])) {
            $status = self::STATUS_NEW;
        }

        $this->status = $status;

        if ($status === self::STATUS_COMPLETED) {
            if ($this->completedAt === null) {
                $this->completedAt = new \DateTime();
            }
        } else {
            $this->completedAt = null;
        }

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = max(0, $position);

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
            if ($this->status === self::STATUS_COMPLETED) {
                $this->status = self::STATUS_NEW;
            }

            return $this;
        }

        $this->completedAt = $completedAt instanceof \DateTime
            ? $completedAt
            : \DateTime::createFromInterface($completedAt);
        $this->status = self::STATUS_COMPLETED;

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
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

    /** @return 'today'|'past'|'future'|null */
    public function getDayBucket(\DateTimeInterface $today): ?string
    {
        if ($this->scheduledDate === null) {
            return null;
        }

        $date = $this->scheduledDate->format('Y-m-d');
        $todayKey = $today->format('Y-m-d');

        if ($date === $todayKey) {
            return 'today';
        }

        return $date < $todayKey ? 'past' : 'future';
    }

    public function showsOnDashboard(): bool
    {
        return $this->scheduledDate !== null && ! $this->isCompleted();
    }
}
