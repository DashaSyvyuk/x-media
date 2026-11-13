<?php

namespace App\Entity;

use App\Repository\AdminPlanColumnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminPlanColumnRepository::class)]
#[ORM\Table(name: 'admin_plan_columns')]
#[ORM\HasLifecycleCallbacks]
class AdminPlanColumn
{
    public const COLORS = AdminPlanBoard::COLORS;

    public const DEFAULTS = [
        ['title' => 'Нове', 'color' => 'blue', 'isDone' => false],
        ['title' => 'В роботі', 'color' => 'amber', 'isDone' => false],
        ['title' => 'Завершено', 'color' => 'lime', 'isDone' => true],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: AdminPlanBoard::class, inversedBy: 'columns')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AdminPlanBoard $board;

    #[ORM\Column(type: 'string', length: 120)]
    private string $title = '';

    #[ORM\Column(type: 'string', length: 20)]
    private string $color = 'blue';

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $position = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isDone = false;

    /** @var Collection<int, AdminPlan> */
    #[ORM\OneToMany(mappedBy: 'planColumn', targetEntity: AdminPlan::class)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $plans;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $now = new \DateTime();
        $this->plans = new ArrayCollection();
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getColorHex(): string
    {
        return self::COLORS[$this->color] ?? self::COLORS['blue'];
    }

    public function setColor(string $color): self
    {
        $this->color = isset(self::COLORS[$color]) ? $color : 'blue';

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

    public function isDone(): bool
    {
        return $this->isDone;
    }

    public function setIsDone(bool $isDone): self
    {
        $this->isDone = $isDone;

        return $this;
    }

    /**
     * @return Collection<int, AdminPlan>
     */
    public function getPlans(): Collection
    {
        return $this->plans;
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
}
