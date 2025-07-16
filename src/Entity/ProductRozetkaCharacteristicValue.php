<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Table("product_rozetka_characteristic_value", indexes={
 *     @Index(columns={"rozetka_product_id", "characteristic_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\ProductRozetkaCharacteristicValueRepository")
 */
class ProductRozetkaCharacteristicValue
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private ?int $id = 0;

    /**
     * @ORM\ManyToOne(targetEntity=RozetkaProduct::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $rozetkaProduct;

    /**
     * @ORM\ManyToOne(targetEntity=RozetkaCharacteristics::class)
     * @ORM\JoinColumn(nullable=false, referencedColumnName="rozetka_id")
     */
    private $characteristic;

    /**
     * @ORM\ManyToOne(targetEntity=RozetkaCharacteristicsValue::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private RozetkaCharacteristicsValue $value;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\RozetkaCharacteristicsValue")
     * @ORM\JoinTable(name="product_rozetka_characteristic_value_value",
     *      joinColumns={@ORM\JoinColumn(name="product_rozetka_characteristic_value_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="rozetka_characteristic_value_id", referencedColumnName="id")}
     * )
     */
    private $values;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
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

    public function setValue(RozetkaCharacteristicsValue $value): void
    {
        $this->value = $value;
    }

    /**
     * @param RozetkaCharacteristicsValue $value
     */
    public function addValue(RozetkaCharacteristicsValue $value): void
    {
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
        return $this->values->filter(function ($value) {
            return $value->getActive();
        });
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
