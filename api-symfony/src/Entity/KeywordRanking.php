<?php

namespace App\Entity;

use App\Repository\KeywordRankingRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KeywordRankingRepository::class)]
#[ORM\Table(name: 'keyword_rankings')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class KeywordRanking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $searchEngine = null;

    #[ORM\Column(length: 20)]
    private ?string $device = null;

    #[ORM\Column(nullable: true)]
    private ?int $searchPage = null;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $checkedAt = null;

    #[ORM\ManyToOne(inversedBy: 'rankings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Keyword $keyword = null;

    #[ORM\ManyToOne(inversedBy: 'keywordRankings')]
    private ?Audit $audit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSearchEngine(): ?string
    {
        return $this->searchEngine;
    }

    public function setSearchEngine(string $searchEngine): static
    {
        $this->searchEngine = $searchEngine;

        return $this;
    }

    public function getDevice(): ?string
    {
        return $this->device;
    }

    public function setDevice(string $device): static
    {
        $this->device = $device;

        return $this;
    }

    public function getSearchPage(): ?int
    {
        return $this->searchPage;
    }

    public function setSearchPage(?int $searchPage): static
    {
        $this->searchPage = $searchPage;

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

    public function getCheckedAt(): ?\DateTimeImmutable
    {
        return $this->checkedAt;
    }

    public function setCheckedAt(\DateTimeImmutable $checkedAt): static
    {
        $this->checkedAt = $checkedAt;

        return $this;
    }

    public function getKeyword(): ?Keyword
    {
        return $this->keyword;
    }

    public function setKeyword(?Keyword $keyword): static
    {
        $this->keyword = $keyword;

        return $this;
    }

    public function getAudit(): ?Audit
    {
        return $this->audit;
    }

    public function setAudit(?Audit $audit): static
    {
        $this->audit = $audit;

        return $this;
    }
}
