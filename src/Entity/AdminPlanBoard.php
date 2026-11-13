<?php

namespace App\Entity;

use App\Repository\AdminPlanBoardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminPlanBoardRepository::class)]
#[ORM\Table(name: 'admin_plan_boards')]
#[ORM\HasLifecycleCallbacks]
class AdminPlanBoard
{
    public const COLORS = [
        'teal'   => '#0d9488',
        'blue'   => '#2563eb',
        'violet' => '#7c3aed',
        'amber'  => '#d97706',
        'rose'   => '#e11d48',
        'slate'  => '#475569',
        'cyan'   => '#0891b2',
        'lime'   => '#65a30d',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\Column(type: 'string', length: 120)]
    private string $title = '';

    #[ORM\Column(type: 'string', length: 20)]
    private string $color = 'teal';

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $position = 0;

    /** @var Collection<int, AdminPlan> */
    #[ORM\OneToMany(mappedBy: 'board', targetEntity: AdminPlan::class, orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $plans;

    /** @var Collection<int, AdminPlanColumn> */
    #[ORM\OneToMany(mappedBy: 'board', targetEntity: AdminPlanColumn::class, orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $columns;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $now = new \DateTime();
        $this->plans = new ArrayCollection();
        $this->columns = new ArrayCollection();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): int
    {
        return $this->id;
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
        return self::COLORS[$this->color] ?? self::COLORS['teal'];
    }

    public function setColor(string $color): self
    {
        $this->color = isset(self::COLORS[$color]) ? $color : 'teal';

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

    /**
     * @return Collection<int, AdminPlan>
     */
    public function getPlans(): Collection
    {
        return $this->plans;
    }

    /**
     * @return Collection<int, AdminPlanColumn>
     */
    public function getColumns(): Collection
    {
        return $this->columns;
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
