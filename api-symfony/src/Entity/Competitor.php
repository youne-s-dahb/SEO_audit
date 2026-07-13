<?php

namespace App\Entity;

use App\Repository\CompetitorRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompetitorRepository::class)]
#[ORM\Table(name: 'competitors')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class Competitor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $competitorUrl = null;

    #[ORM\Column(nullable: true)]
    private ?int $rankingPosition = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $checkedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompetitorUrl(): ?string
    {
        return $this->competitorUrl;
    }

    public function setCompetitorUrl(string $competitorUrl): static
    {
        $this->competitorUrl = $competitorUrl;

        return $this;
    }

    public function getRankingPosition(): ?int
    {
        return $this->rankingPosition;
    }

    public function setRankingPosition(?int $rankingPosition): static
    {
        $this->rankingPosition = $rankingPosition;

        return $this;
    }

    public function getCheckedAt(): ?\DateTimeImmutable
    {
        return $this->checkedAt;
    }

    public function setCheckedAt(\DateTimeImmutable $checkedAt): static
    {
        $this->checkedAt = $checkedAt;

        return $this;
    }
}
