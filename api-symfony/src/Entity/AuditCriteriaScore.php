<?php

namespace App\Entity;

use App\Repository\AuditCriteriaScoreRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditCriteriaScoreRepository::class)]
#[ORM\Table(name: 'audit_criteria_scores')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class AuditCriteriaScore
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
