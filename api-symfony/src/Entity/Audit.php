<?php

namespace App\Entity;

use App\Repository\AuditRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditRepository::class)]
#[ORM\Table(name: 'audits')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class Audit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $site_id = null;

    #[ORM\Column]
    private ?int $requested_by = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?float $global_score = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $score_color = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_https = null;

    #[ORM\Column(nullable: true)]
    private ?bool $has_robots_txt = null;

    #[ORM\Column(nullable: true)]
    private ?bool $has_sitemap_xml = null;

    #[ORM\Column(nullable: true)]
    private ?bool $is_mobile_friendly = null;

    #[ORM\Column(nullable: true)]
    private ?int $page_load_time_ms = null;

    #[ORM\Column(nullable: true)]
    private ?int $pagespeed_mobile_score = null;

    #[ORM\Column(nullable: true)]
    private ?int $pagespeed_desktop_score = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $error_message = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $started_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $finished_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $created_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSiteId(): ?int
    {
        return $this->site_id;
    }

    public function setSiteId(int $site_id): static
    {
        $this->site_id = $site_id;

        return $this;
    }

    public function getRequestedBy(): ?int
    {
        return $this->requested_by;
    }

    public function setRequestedBy(int $requested_by): static
    {
        $this->requested_by = $requested_by;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getGlobalScore(): ?float
    {
        return $this->global_score;
    }

    public function setGlobalScore(?float $global_score): static
    {
        $this->global_score = $global_score;

        return $this;
    }

    public function getScoreColor(): ?string
    {
        return $this->score_color;
    }

    public function setScoreColor(?string $score_color): static
    {
        $this->score_color = $score_color;

        return $this;
    }

    public function isHttps(): ?bool
    {
        return $this->is_https;
    }

    public function setIsHttps(?bool $is_https): static
    {
        $this->is_https = $is_https;

        return $this;
    }

    public function hasRobotsTxt(): ?bool
    {
        return $this->has_robots_txt;
    }

    public function setHasRobotsTxt(?bool $has_robots_txt): static
    {
        $this->has_robots_txt = $has_robots_txt;

        return $this;
    }

    public function hasSitemapXml(): ?bool
    {
        return $this->has_sitemap_xml;
    }

    public function setHasSitemapXml(?bool $has_sitemap_xml): static
    {
        $this->has_sitemap_xml = $has_sitemap_xml;

        return $this;
    }

    public function isMobileFriendly(): ?bool
    {
        return $this->is_mobile_friendly;
    }

    public function setIsMobileFriendly(?bool $is_mobile_friendly): static
    {
        $this->is_mobile_friendly = $is_mobile_friendly;

        return $this;
    }

    public function getPageLoadTimeMs(): ?int
    {
        return $this->page_load_time_ms;
    }

    public function setPageLoadTimeMs(?int $page_load_time_ms): static
    {
        $this->page_load_time_ms = $page_load_time_ms;

        return $this;
    }

    public function getPagespeedMobileScore(): ?int
    {
        return $this->pagespeed_mobile_score;
    }

    public function setPagespeedMobileScore(?int $pagespeed_mobile_score): static
    {
        $this->pagespeed_mobile_score = $pagespeed_mobile_score;

        return $this;
    }

    public function getPagespeedDesktopScore(): ?int
    {
        return $this->pagespeed_desktop_score;
    }

    public function setPagespeedDesktopScore(?int $pagespeed_desktop_score): static
    {
        $this->pagespeed_desktop_score = $pagespeed_desktop_score;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->error_message;
    }

    public function setErrorMessage(?string $error_message): static
    {
        $this->error_message = $error_message;

        return $this;
    }

    public function getStartedAt(): ?\DateTime
    {
        return $this->started_at;
    }

    public function setStartedAt(?\DateTime $started_at): static
    {
        $this->started_at = $started_at;

        return $this;
    }

    public function getFinishedAt(): ?\DateTime
    {
        return $this->finished_at;
    }

    public function setFinishedAt(?\DateTime $finished_at): static
    {
        $this->finished_at = $finished_at;

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
