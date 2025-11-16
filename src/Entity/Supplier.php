<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table("suppliers", indexes: [
    new ORM\Index(columns: ["title", "surname"])
])]
#[ORM\Entity(repositoryClass: "App\Repository\SupplierRepository")]
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
    #[ORM\ManyToOne(targetEntity: "App\Entity\Currency")]
    private Currency $currency;

    #[ORM\Column(type: "boolean")]
    private bool $active = true;

    #[ORM\OneToMany(mappedBy: "supplier", targetEntity: "App\Entity\SupplierProduct", cascade: ["all"], orphanRemoval: true)]
    private $products;

    #[ORM\Column(type: "datetime")]
    public DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    public DateTime $updatedAt;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $surname
     */
    public function setSurname(?string $surname): void
    {
        $this->surname = $surname;
    }

    /**
     * @return string|null
     */
    public function getSurname(): ?string
    {
        return $this->surname;
    }

    /**
     * @param string|null $phone
     */
    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string|null $email
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $address
     */
    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    /**
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * @param string|null $bankAccount
     */
    public function setBankAccount(?string $bankAccount): void
    {
        $this->bankAccount = $bankAccount;
    }

    /**
     * @return string|null
     */
    public function getBankAccount(): ?string
    {
        return $this->bankAccount;
    }

    /**
     * @param Currency $currency
     */
    public function setCurrency(Currency $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return Currency
     */
    public function getCurrency(): Currency
    {
        return $this->currency;
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

    public function getProducts()
    {
        return $this->products;
    }

    public function setProducts($products): void
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
        return (string) $this->title;
    }
}
