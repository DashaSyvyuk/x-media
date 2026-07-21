<?php

namespace App\Entity;

use App\Repository\AdminPushSubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminPushSubscriptionRepository::class)]
#[ORM\Table(name: 'admin_push_subscriptions')]
#[ORM\HasLifecycleCallbacks]
class AdminPushSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: AdminUser::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AdminUser $user;

    #[ORM\Column(type: 'text')]
    private string $endpoint = '';

    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private string $endpointHash = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $p256dh = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $auth = '';

    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $now = new \DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): AdminUser
    {
        return $this->user;
    }

    public function setUser(AdminUser $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function setEndpoint(string $endpoint): self
    {
        $this->endpoint = $endpoint;
        $this->endpointHash = hash('sha256', $endpoint);

        return $this;
    }

    public function getEndpointHash(): string
    {
        return $this->endpointHash;
    }

    public function getP256dh(): string
    {
        return $this->p256dh;
    }

    public function setP256dh(string $p256dh): self
    {
        $this->p256dh = $p256dh;

        return $this;
    }

    public function getAuth(): string
    {
        return $this->auth;
    }

    public function setAuth(string $auth): self
    {
        $this->auth = $auth;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        if ($this->endpointHash === '' && $this->endpoint !== '') {
            $this->endpointHash = hash('sha256', $this->endpoint);
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
