<?php

namespace App\Entity;

use App\Repository\RozetkaCharacteristicsRepository;
use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

#[ORM\Table("characteristics_rozetka", indexes: [
    new ORM\Index(columns: ["title"]),
    new ORM\Index(columns: ["rozetka_id"])
])]
#[ORM\Entity(repositoryClass: RozetkaCharacteristicsRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class RozetkaCharacteristics
{
    use DateStorageTrait;

    #[ORM\Id()]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $rozetkaId = 0;

    /** @var ArrayCollection<int, Category>|PersistentCollection<int, Category> $categories */
    #[ORM\ManyToMany(targetEntity: Category::class)]
    #[ORM\JoinTable(
        name: "rozetka_characteristic_category",
        joinColumns: [
            new ORM\JoinColumn(name: "rozetka_characteristic_id", referencedColumnName: "rozetka_id")
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: "category_id", referencedColumnName: "id")
        ]
    )]
    private ArrayCollection|PersistentCollection $categories;

    #[ORM\Column(type: "string")]
    private string $title = "";

    #[ORM\Column(type: "string")]
    private string $type = "";

    #[ORM\Column(type: "boolean")]
    private bool $filterType = true;

    #[ORM\Column(type: "string")]
    private ?string $unit = "";

    #[ORM\Column(type: "boolean")]
    private bool $endToEndParameter = true;

    #[ORM\Column(type: "boolean")]
    private bool $active = true;

    /** @var ArrayCollection<int, RozetkaCharacteristicsValue>|PersistentCollection<int, RozetkaCharacteristicsValue> $values  */
    #[ORM\OneToMany(
        targetEntity: RozetkaCharacteristicsValue::class,
        mappedBy: "characteristic",
        cascade: ["all"],
        fetch: "EAGER",
        orphanRemoval: true
    )]
    private ArrayCollection|PersistentCollection $values;

    #[ORM\Column(type: "datetime")]
    public DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    public DateTime $updatedAt;

    public function __construct()
    {
        $this->values = new ArrayCollection();
        $this->categories = new ArrayCollection();
    }

    public function setRozetkaId(int $rozetkaId): void
    {
        $this->rozetkaId = $rozetkaId;
    }

    public function getRozetkaId(): int
    {
        return $this->rozetkaId;
    }

    public function addCategory(Category $category): void
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
        }
    }

    public function removeCategory(Category $category): bool
    {
        return $this->categories->removeElement($category);
    }

    /**
     * @return ArrayCollection<int, Category>|PersistentCollection<int, Category>
     */
    public function getCategories(): ArrayCollection|PersistentCollection
    {
        return $this->categories;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setFilterType(bool $filterType): void
    {
        $this->filterType = $filterType;
    }

    public function getFilterType(): bool
    {
        return $this->filterType;
    }

    public function setUnit(?string $unit): void
    {
        $this->unit = $unit;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setEndToEndParameter(bool $endToEndParameter): void
    {
        $this->endToEndParameter = $endToEndParameter;
    }

    public function getEndToEndParameter(): bool
    {
        return $this->endToEndParameter;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function addValue(RozetkaCharacteristicsValue $value): void
    {
        $value->setCharacteristic($this);
        if (!$this->values->contains($value)) {
            $this->values[] = $value;
        }
    }

    public function removeValue(RozetkaCharacteristicsValue $value): bool
    {
        return $this->values->removeElement($value);
    }

    /**
     * @return ArrayCollection<int, RozetkaCharacteristicsValue>|PersistentCollection<int, RozetkaCharacteristicsValue>
     */
    public function getValues(): ArrayCollection|PersistentCollection
    {
        return $this->values;
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
        return $this->title;
    }
}
