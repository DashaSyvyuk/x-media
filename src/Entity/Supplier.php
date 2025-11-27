<?php

namespace App\Entity;

use App\Repository\SupplierRepository;
use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

#[ORM\Table("suppliers", indexes: [
    new ORM\Index(columns: ["title", "surname"])
])]
#[ORM\Entity(repositoryClass: SupplierRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class Supplier
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\Column(type: "string")]
    private string $title = "";

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $name = "";

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $surname = "";

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $phone = "";

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $email = "";

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $address = "";

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $bankAccount = "";

    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[ORM\ManyToOne(targetEntity: Currency::class)]
    private Currency $currency;

    #[ORM\Column(type: "boolean")]
    private bool $active = true;

    /** @var ArrayCollection<int, SupplierProduct>|PersistentCollection<int, SupplierProduct> $products */
    #[ORM\OneToMany(
        targetEntity: SupplierProduct::class,
        mappedBy: "supplier",
        cascade: ["all"],
        orphanRemoval: true
    )]
    private ArrayCollection|PersistentCollection $products;

    #[ORM\Column(type: "datetime")]
    public DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    public DateTime $updatedAt;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setSurname(?string $surname): void
    {
        $this->surname = $surname;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setBankAccount(?string $bankAccount): void
    {
        $this->bankAccount = $bankAccount;
    }

    public function getBankAccount(): ?string
    {
        return $this->bankAccount;
    }

    public function setCurrency(Currency $currency): void
    {
        $this->currency = $currency;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * @return ArrayCollection<int, SupplierProduct>|PersistentCollection<int, SupplierProduct>
     */
    public function getProducts(): ArrayCollection|PersistentCollection
    {
        return $this->products;
    }

    /**
     * @param ArrayCollection<int, SupplierProduct>|PersistentCollection<int, SupplierProduct> $products
     */
    public function setProducts(ArrayCollection|PersistentCollection $products): void
    {
        if (count($products) > 0) {
            foreach ($products as $product) {
                $this->addProduct($product);
            }
        }
    }

    public function addProduct(SupplierProduct $product): void
    {
        if (!$this->products->contains($product)) {
            $product->setSupplier($this);
            $this->products[] = $product;
        }
    }

    public function removeProduct(SupplierProduct $product): void
    {
        if ($this->products->contains($product)) {
            $this->products->removeElement($product);
        }
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
