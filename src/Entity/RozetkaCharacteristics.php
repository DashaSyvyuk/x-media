<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table("characteristics_rozetka", indexes: [
    new ORM\Index(columns: ["title"]),
    new ORM\Index(columns: ["rozetka_id"])
])]
#[ORM\Entity(repositoryClass: "App\Repository\RozetkaCharacteristicsRepository")]
#[ORM\HasLifecycleCallbacks()]
class RozetkaCharacteristics
{
    use DateStorageTrait;

    #[ORM\Id()]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $rozetkaId = 0;

    #[ORM\ManyToMany(targetEntity: "App\Entity\Category")]
    #[ORM\JoinTable(
        name: "rozetka_characteristic_category",
        joinColumns: [
            new ORM\JoinColumn(name: "rozetka_characteristic_id", referencedColumnName: "rozetka_id")
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: "category_id", referencedColumnName: "id")
        ]
    )]
    private $categories;

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

    #[ORM\OneToMany(mappedBy: "characteristic", targetEntity: "App\Entity\RozetkaCharacteristicsValue", cascade: ["all"], fetch: "EAGER", orphanRemoval: true)]
    private $values;

    #[ORM\Column(type: "datetime")]
    public DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    public DateTime $updatedAt;

    public function __construct()
    {
        $this->values = new ArrayCollection();
        $this->categories = new ArrayCollection();
    }

    /**
     * @param int $rozetkaId
     */
    public function setRozetkaId(int $rozetkaId): void
    {
        $this->rozetkaId = $rozetkaId;
    }

    /**
     * @return int
     */
    public function getRozetkaId(): int
    {
        return $this->rozetkaId;
    }

    /**
     * @param Category $category
     */
    public function addCategory(Category $category): void
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
        }
    }

    /**
     * @param Category $category
     * @return bool
     */
    public function removeCategory(Category $category): bool
    {
        return $this->categories->removeElement($category);
    }

    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param bool $filterType
     */
    public function setFilterType(bool $filterType): void
    {
        $this->filterType = $filterType;
    }

    /**
     * @return bool
     */
    public function getFilterType(): bool
    {
        return $this->filterType;
    }

    /**
     * @param string|null $unit
     */
    public function setUnit(?string $unit): void
    {
        $this->unit = $unit;
    }

    /**
     * @return string|null
     */
    public function getUnit(): ?string
    {
        return $this->unit;
    }

    /**
     * @param bool $endToEndParameter
     */
    public function setEndToEndParameter(bool $endToEndParameter): void
    {
        $this->endToEndParameter = $endToEndParameter;
    }

    /**
     * @return bool
     */
    public function getEndToEndParameter(): bool
    {
        return $this->endToEndParameter;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return bool
     */
    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * @param RozetkaCharacteristicsValue $value
     */
    public function addValue(RozetkaCharacteristicsValue $value): void
    {
        $value->setCharacteristic($this);
        if (!$this->values->contains($value)) {
            $this->values[] = $value;
        }
    }

    /**
     * @param RozetkaCharacteristicsValue $value
     * @return bool
     */
    public function removeValue(RozetkaCharacteristicsValue $value): bool
    {
        return $this->values->removeElement($value);
    }

    public function getValues()
    {
        return $this->values;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
