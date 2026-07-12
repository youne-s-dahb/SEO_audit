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

    #[ORM\Column]
    private ?int $keyword_id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $competitor_url = null;

    #[ORM\Column(nullable: true)]
    private ?int $ranking_position = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $cheked_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKeywordId(): ?int
    {
        return $this->keyword_id;
    }

    public function setKeywordId(int $keyword_id): static
    {
        $this->keyword_id = $keyword_id;

        return $this;
    }

    public function getCompetitorUrl(): ?string
    {
        return $this->competitor_url;
    }

    public function setCompetitorUrl(string $competitor_url): static
    {
        $this->competitor_url = $competitor_url;

        return $this;
    }

    public function getRankingPosition(): ?int
    {
        return $this->ranking_position;
    }

    public function setRankingPosition(?int $ranking_position): static
    {
        $this->ranking_position = $ranking_position;

        return $this;
    }

    public function getChekedAt(): ?\DateTime
    {
        return $this->cheked_at;
    }

    public function setChekedAt(?\DateTime $cheked_at): static
    {
        $this->cheked_at = $cheked_at;

        return $this;
    }
}
