<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Table("characteristics_rozetka", indexes={
 *     @Index(columns={"title"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\RozetkaCharacteristicsRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class RozetkaCharacteristics
{
    use DateStorageTrait;

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private int $rozetkaId = 0;

    /**
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     * @ORM\ManyToOne(targetEntity="Category")
     */
    private ?Category $category = null;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private string $title = "";

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private string $type = "";

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private bool $filterType = true;

    /**
     * @var string|null
     * @ORM\Column(type="string")
     */
    private ?string $unit = "";

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private bool $endToEndParameter = true;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private bool $active = true;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\RozetkaCharacteristicsValue",
     *     mappedBy="characteristic", cascade={"all"}, orphanRemoval=true, fetch="EAGER")
     */
    private $values;

    /**
     * @ORM\Column(type="datetime")
     */
    public DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    public DateTime $updatedAt;

    public function __construct()
    {
        $this->values = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
     * @param Category|null $category
     */
    public function setCategory(?Category $category): void
    {
        $this->category = $category;
    }

    /**
     * @return Category|null
     */
    public function getCategory(): ?Category
    {
        return $this->category;
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
