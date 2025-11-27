<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table("currency", indexes: [
    new ORM\Index(columns: ["code"]),
])]
#[ORM\Entity()]
class Currency
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\Column(type: "string")]
    private string $title = "";

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $shortTitle = "";

    #[ORM\Column(type: "string")]
    private string $code = "";

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private float $exchangeRate = 0.00;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setShortTitle(?string $shortTitle): void
    {
        $this->shortTitle = $shortTitle;
    }

    public function getShortTitle(): ?string
    {
        return $this->shortTitle;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setExchangeRate(float $exchangeRate): void
    {
        $this->exchangeRate = $exchangeRate;
    }

    public function getExchangeRate(): float
    {
        return $this->exchangeRate;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
