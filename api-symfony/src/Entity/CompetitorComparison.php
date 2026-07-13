<?php

namespace App\Entity;

use App\Repository\CompetitorComparisonRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\DBAL\Types\Types;
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

    #[ORM\Column(length: 100)]
    private ?string $criterion = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $siteValue = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $competitorValue = null;

    public function getId(): ?int
    {
        return $this->id;
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
        return $this->siteValue;
    }

    public function setSiteValue(?string $siteValue): static
    {
        $this->siteValue = $siteValue;

        return $this;
    }

    public function getCompetitorValue(): ?string
    {
        return $this->competitorValue;
    }

    public function setCompetitorValue(?string $competitorValue): static
    {
        $this->competitorValue = $competitorValue;

        return $this;
    }
}
