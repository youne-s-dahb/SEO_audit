<?php

namespace App\Entity;

use App\Repository\AuditCriteriaScoreRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\DBAL\Types\Types;
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

    #[ORM\Column]
    private ?int $audit_id = null;

    #[ORM\Column(length: 255)]
    private ?string $criterion = null;

    #[ORM\Column]
    private ?float $score = null;

    #[ORM\Column(length: 10)]
    private ?string $color_code = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(float $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getColorCode(): ?string
    {
        return $this->color_code;
    }

    public function setColorCode(string $color_code): static
    {
        $this->color_code = $color_code;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }
}
