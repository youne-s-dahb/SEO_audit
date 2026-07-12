<?php

namespace App\Entity;

use App\Repository\CompetitorComparisonRepository;
use Doctrine\DBAL\Types\Types;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompetitorComparisonRepository::class)]
#[ORM\Table(name: 'competitor_comparisons')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class CompetitorComparison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $competitor_id = null;

    #[ORM\Column]
    private ?int $audit_id = null;

    #[ORM\Column(length: 100)]
    private ?string $criterion = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $site_value = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $competitor_value = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompetitorId(): ?int
    {
        return $this->competitor_id;
    }

    public function setCompetitorId(int $competitor_id): static
    {
        $this->competitor_id = $competitor_id;

        return $this;
    }

    public function getAuditId(): ?int
    {
        return $this->audit_id;
    }

    public function setAuditId(int $audit_id): static
    {
        $this->audit_id = $audit_id;

        return $this;
    }

    public function getCriterion(): ?string
    {
        return $this->criterion;
    }

    public function setCriterion(string $criterion): static
    {
        $this->criterion = $criterion;

        return $this;
    }

    public function getSiteValue(): ?string
    {
        return $this->site_value;
    }

    public function setSiteValue(?string $site_value): static
    {
        $this->site_value = $site_value;

        return $this;
    }

    public function getCompetitorValue(): ?string
    {
        return $this->competitor_value;
    }

    public function setCompetitorValue(?string $competitor_value): static
    {
        $this->competitor_value = $competitor_value;

        return $this;
    }
}