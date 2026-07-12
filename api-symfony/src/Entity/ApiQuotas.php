<?php

namespace App\Entity;

use App\Repository\ApiQuotasRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiQuotasRepository::class)]
#[ORM\Table(name: 'audit_quotas')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class ApiQuotas
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $user_id = null;

    #[ORM\Column(length: 50)]
    private ?string $api_name = null;

    #[ORM\Column]
    private ?int $daily_limit = null;

    #[ORM\Column]
    private ?int $used_today = null;

    #[ORM\Column]
    private ?\DateTime $reset_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getApiName(): ?string
    {
        return $this->api_name;
    }

    public function setApiName(string $api_name): static
    {
        $this->api_name = $api_name;

        return $this;
    }

    public function getDailyLimit(): ?int
    {
        return $this->daily_limit;
    }

    public function setDailyLimit(int $daily_limit): static
    {
        $this->daily_limit = $daily_limit;

        return $this;
    }

    public function getUsedToday(): ?int
    {
        return $this->used_today;
    }

    public function setUsedToday(int $used_today): static
    {
        $this->used_today = $used_today;

        return $this;
    }

    public function getResetAt(): ?\DateTime
    {
        return $this->reset_at;
    }

    public function setResetAt(\DateTime $reset_at): static
    {
        $this->reset_at = $reset_at;

        return $this;
    }
}
