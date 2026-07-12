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

    #[ORM\Column]
    private ?int $keyword_id = null;

    #[ORM\Column]
    private ?int $audit_id = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $search_engine = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $device = null;

    #[ORM\Column(nullable: true)]
    private ?int $search_page = null;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $checked_at = null;

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

    public function getAuditId(): ?int
    {
        return $this->audit_id;
    }

    public function setAuditId(int $audit_id): static
    {
        $this->audit_id = $audit_id;

        return $this;
    }

    public function getSearchEngine(): ?string
    {
        return $this->search_engine;
    }

    public function setSearchEngine(?string $search_engine): static
    {
        $this->search_engine = $search_engine;

        return $this;
    }

    public function getDevice(): ?string
    {
        return $this->device;
    }

    public function setDevice(?string $device): static
    {
        $this->device = $device;

        return $this;
    }

    public function getSearchPage(): ?int
    {
        return $this->search_page;
    }

    public function setSearchPage(?int $search_page): static
    {
        $this->search_page = $search_page;

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

    public function getCheckedAt(): ?\DateTime
    {
        return $this->checked_at;
    }

    public function setCheckedAt(?\DateTime $checked_at): static
    {
        $this->checked_at = $checked_at;

        return $this;
    }
}
