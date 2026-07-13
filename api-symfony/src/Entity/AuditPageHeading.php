<?php

namespace App\Entity;

use App\Repository\AuditPageHeadingRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditPageHeadingRepository::class)]
#[ORM\Table(name: 'audit_page_headings')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class AuditPageHeading
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 5)]
    private ?string $headingLevel = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    #[ORM\ManyToOne(inversedBy: 'heading')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AuditPage $auditPage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHeadingLevel(): ?string
    {
        return $this->headingLevel;
    }

    public function setHeadingLevel(string $headingLevel): static
    {
        $this->headingLevel = $headingLevel;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getAuditPage(): ?AuditPage
    {
        return $this->auditPage;
    }

    public function setAuditPage(?AuditPage $auditPage): static
    {
        $this->auditPage = $auditPage;

        return $this;
    }
}
