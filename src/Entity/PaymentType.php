<?php

namespace App\Entity;

use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table("payment_type")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class PaymentType
{
    use DateStorageTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private int $id;

    /**
     * @ORM\Column(type="string")
     */
    private string $title = "";

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): int
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

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function __toString():string
    {
        return (string) $this->title;
    }
}
