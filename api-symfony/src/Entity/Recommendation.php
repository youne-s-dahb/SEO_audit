<?php

namespace App\Entity;

use App\Repository\RecommendationRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\DBAL\Types\Types;
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

    #[ORM\Column]
    private ?int $audit_id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    private ?string $priority = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $impact_level = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_done = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $created_at = null;

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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getImpactLevel(): ?string
    {
        return $this->impact_level;
    }

    public function setImpactLevel(?string $impact_level): static
    {
        $this->impact_level = $impact_level;

        return $this;
    }

    public function isDone(): ?bool
    {
        return $this->is_done;
    }

    public function setIsDone(?bool $is_done): static
    {
        $this->is_done = $is_done;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }
}
