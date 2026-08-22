<?php

namespace App\Entity;

use App\Repository\AuditKeywordDensityRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditKeywordDensityRepository::class)]
#[ORM\Table(name: 'audit_keyword_density')]
#[ApiResource]
// Permet ?auditPage.audit=30 (toute la densite de mots-cles d'un audit)
// ou ?auditPage=5 (d'une page precise).
#[ApiFilter(SearchFilter::class, properties: [
    'auditPage' => 'exact',
    'auditPage.audit' => 'exact',
])]
class AuditKeywordDensity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $keyword = null;

    #[ORM\Column(nullable: true)]
    private ?int $occurrences = null;

    #[ORM\Column(nullable: true)]
    private ?float $densityPercent = null;

    #[ORM\ManyToOne(inversedBy: 'keywordDensities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AuditPage $auditPage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function setKeyword(?string $keyword): static
    {
        $this->keyword = $keyword;

        return $this;
    }

    public function getOccurrences(): ?int
    {
        return $this->occurrences;
    }

    public function setOccurrences(?int $occurrences): static
    {
        $this->occurrences = $occurrences;

        return $this;
    }

    public function getDensityPercent(): ?float
    {
        return $this->densityPercent;
    }

    public function setDensityPercent(?float $densityPercent): static
    {
        $this->densityPercent = $densityPercent;

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