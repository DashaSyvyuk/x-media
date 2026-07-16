<?php

namespace App\Entity;

use App\Repository\PlanningGoodBatchRepository;
use App\Traits\DateStorageTrait;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'planning_good_batches')]
#[ORM\Entity(repositoryClass: PlanningGoodBatchRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PlanningGoodBatch
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $id = 0;

    #[ORM\Column(type: 'date')]
    private DateTimeInterface $recordedDate;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    /** @var Collection<int, PlanningGood> */
    #[ORM\OneToMany(mappedBy: 'batch', targetEntity: PlanningGood::class, cascade: ['all'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'DESC'])]
    private Collection $goods;

    #[ORM\Column(type: 'datetime')]
    public DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    public DateTime $updatedAt;

    public function __construct()
    {
        $this->goods = new ArrayCollection();
        $this->recordedDate = new DateTime('today');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRecordedDate(): DateTimeInterface
    {
        return $this->recordedDate;
    }

    public function setRecordedDate(DateTimeInterface $recordedDate): void
    {
        $this->recordedDate = $recordedDate;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $name = $name !== null ? trim($name) : null;
        $this->name = $name !== '' ? $name : null;
    }

    public function getDisplayName(): string
    {
        return $this->name ?: 'Без назви';
    }

    /**
     * @return Collection<int, PlanningGood>
     */
    public function getGoods(): Collection
    {
        return $this->goods;
    }

    public function addGood(PlanningGood $good): void
    {
        if (! $this->goods->contains($good)) {
            $this->goods->add($good);
            $good->setBatch($this);
        }
    }

    public function removeGood(PlanningGood $good): void
    {
        $this->goods->removeElement($good);
    }

    /**
     * @return array{count: int, sold_count: int, purchase: float, delivery: float, sale: float, margin: float}
     */
    public function getTotals(): array
    {
        $purchase = 0.0;
        $delivery = 0.0;
        $sale = 0.0;
        $margin = 0.0;
        $soldCount = 0;

        foreach ($this->goods as $good) {
            $purchase += $good->getTotalPurchaseValue();
            $delivery += $good->getDeliveryPrice();
            $sale += $good->getTotalSaleValue();
            $itemMargin = $good->getMargin();
            if ($itemMargin !== null) {
                $margin += $itemMargin;
            }
            if ($good->isSold()) {
                ++$soldCount;
            }
        }

        return [
            'count'      => $this->goods->count(),
            'sold_count' => $soldCount,
            'purchase'   => $purchase,
            'delivery'   => $delivery,
            'sale'       => $sale,
            'margin'     => $margin,
        ];
    }
}
