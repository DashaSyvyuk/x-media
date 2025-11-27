<?php

namespace App\Entity;

use App\Repository\CategoryFeedPriceRepository;
use App\Traits\DateStorageTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table("category_feed_price")]
#[ORM\Entity(repositoryClass: CategoryFeedPriceRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class CategoryFeedPrice
{
    use DateStorageTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", options: ["unsigned" => true])]
    private int $id;

    #[ORM\JoinColumn(onDelete: "CASCADE")]
    #[ORM\ManyToOne(targetEntity: Category::class)]
    private Category $category;

    #[ORM\JoinColumn(onDelete: "CASCADE")]
    #[ORM\ManyToOne(targetEntity: Feed::class)]
    private Feed $feed;

    #[ORM\Column(type: "integer")]
    private int $ourPercent = 0;

    #[ORM\Column(type: "integer")]
    private int $fee = 0;

    #[ORM\Column(type: "datetime")]
    public DateTime $createdAt;

    #[ORM\Column(type: "datetime")]
    public DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setFeed(Feed $feed): void
    {
        $this->feed = $feed;
    }

    public function getFeed(): Feed
    {
        return $this->feed;
    }

    public function setOurPercent(int $ourPercent): void
    {
        $this->ourPercent = $ourPercent;
    }

    public function getOurPercent(): int
    {
        return $this->ourPercent;
    }

    public function setFee(int $fee): void
    {
        $this->fee = $fee;
    }

    public function getFee(): int
    {
        return $this->fee;
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
        return $this->feed->getType();
    }
}
