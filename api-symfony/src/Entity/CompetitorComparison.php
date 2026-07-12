<?php

namespace App\Entity;

use App\Repository\CompetitorComparisonRepository;
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

    public function getId(): ?int
    {
        return $this->id;
    }
}
