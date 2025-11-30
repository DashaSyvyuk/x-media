<?php

namespace App\Entity;

use App\Repository\ProductRozetkaCharacteristicValueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

#[ORM\Table("product_rozetka_characteristic_value", indexes: [
    new ORM\Index(columns: ["rozetka_product_id", "characteristic_id"])
])]
#[ORM\Entity(repositoryClass: ProductRozetkaCharacteristicValueRepository::class)]
class ProductRozetkaCharacteristicValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private ?int $id = 0;

    #[ORM\ManyToOne(targetEntity: RozetkaProduct::class, inversedBy: "values")]
    #[ORM\JoinColumn(nullable: false)]
    private RozetkaProduct $rozetkaProduct;

    #[ORM\ManyToOne(targetEntity: RozetkaCharacteristics::class)]
    #[ORM\JoinColumn(referencedColumnName: "rozetka_id", nullable: false)]
    private RozetkaCharacteristics $characteristic;

    #[ORM\ManyToOne(targetEntity: RozetkaCharacteristicsValue::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?RozetkaCharacteristicsValue $value = null;

    /** @var ArrayCollection<int, RozetkaCharacteristicsValue>|PersistentCollection<int, RozetkaCharacteristicsValue> $values  */
    #[ORM\ManyToMany(targetEntity: RozetkaCharacteristicsValue::class)]
    #[ORM\JoinTable(
        name: 'product_rozetka_characteristic_value_value',
        joinColumns: [
            new ORM\JoinColumn(
                name: 'product_rozetka_characteristic_value_id',
                referencedColumnName: 'id'
            )
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(
                name: 'rozetka_characteristic_value_id',
                referencedColumnName: 'id'
            )
        ]
    )]
    private ArrayCollection|PersistentCollection $values;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $stringValue = "";

    public function __construct()
    {
        $this->values = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getRozetkaProduct(): RozetkaProduct
    {
        return $this->rozetkaProduct;
    }

    public function setRozetkaProduct(RozetkaProduct $rozetkaProduct): void
    {
        $this->rozetkaProduct = $rozetkaProduct;
    }

    public function getCharacteristic(): ?RozetkaCharacteristics
    {
        return $this->characteristic;
    }

    public function setCharacteristic(RozetkaCharacteristics $characteristic): void
    {
        $this->characteristic = $characteristic;
    }

    public function getValue(): ?RozetkaCharacteristicsValue
    {
        return $this->value;
    }

    public function setValue(?RozetkaCharacteristicsValue $value): void
    {
        $this->value = $value;
    }

    public function addValue(RozetkaCharacteristicsValue $value): void
    {
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

    public function getStringValue(): ?string
    {
        return $this->stringValue;
    }

    public function setStringValue(?string $stringValue): void
    {
        $this->stringValue = $stringValue;
    }
}
