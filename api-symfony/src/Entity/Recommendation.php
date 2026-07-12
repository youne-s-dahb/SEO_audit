<?php

namespace App\Entity;

use App\Repository\RecommendationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecommendationRepository::class)]
#[ORM\Table(name: 'recommendations')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class Recommendation
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
