<?php

namespace App\Entity;

use App\Repository\AuditReportRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditReportRepository::class)]
#[ORM\Table(name: 'audit_reports')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class AuditReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $audit_id = null;

    #[ORM\Column(length: 10)]
    private ?string $format = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $file_path = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $generated_at = null;

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

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->file_path;
    }

    public function setFilePath(string $file_path): static
    {
        $this->file_path = $file_path;

        return $this;
    }

    public function getGeneratedAt(): ?\DateTime
    {
        return $this->generated_at;
    }

    public function setGeneratedAt(?\DateTime $generated_at): static
    {
        $this->generated_at = $generated_at;

        return $this;
    }
}
