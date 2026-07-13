<?php

namespace App\Entity;

use App\Repository\ApiQuotaRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiQuotaRepository::class)]
#[ORM\Table(name: 'api_quotas')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class ApiQuota
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $apiName = null;

    #[ORM\Column]
    private ?int $dailyLimit = null;

    #[ORM\Column]
    private ?int $usedToday = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $resetAt = null;

    #[ORM\ManyToOne(inversedBy: 'apiQuotas')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $account = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApiName(): ?string
    {
        return $this->apiName;
    }

    public function setApiName(string $apiName): static
    {
        $this->apiName = $apiName;

        return $this;
    }

    public function getDailyLimit(): ?int
    {
        return $this->dailyLimit;
    }

    public function setDailyLimit(int $dailyLimit): static
    {
        $this->dailyLimit = $dailyLimit;

        return $this;
    }

    public function getUsedToday(): ?int
    {
        return $this->usedToday;
    }

    public function setUsedToday(int $usedToday): static
    {
        $this->usedToday = $usedToday;

        return $this;
    }

    public function getResetAt(): ?\DateTime
    {
        return $this->resetAt;
    }

    public function setResetAt(\DateTime $resetAt): static
    {
        $this->resetAt = $resetAt;

        return $this;
    }

    public function getAccount(): ?User
    {
        return $this->account;
    }

    public function setAccount(?User $account): static
    {
        $this->account = $account;

        return $this;
    }
}
