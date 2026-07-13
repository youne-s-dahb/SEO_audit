<?php

namespace App\Entity;

use App\Repository\KeywordRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KeywordRepository::class)]
#[ORM\Table(name: 'keywords')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class Keyword
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $keyword = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'keywords')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $site = null;

    /**
     * @var Collection<int, KeywordRanking>
     */
    #[ORM\OneToMany(targetEntity: KeywordRanking::class, mappedBy: 'keyword')]
    private Collection $rankings;

    /**
     * @var Collection<int, Competitor>
     */
    #[ORM\OneToMany(targetEntity: Competitor::class, mappedBy: 'keyword')]
    private Collection $competitors;

    /**
     * @var Collection<int, Alert>
     */
    #[ORM\OneToMany(targetEntity: Alert::class, mappedBy: 'keyword')]
    private Collection $alerts;

    public function __construct()
    {
        $this->rankings = new ArrayCollection();
        $this->competitors = new ArrayCollection();
        $this->alerts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function setKeyword(string $keyword): static
    {
        $this->keyword = $keyword;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): static
    {
        $this->site = $site;

        return $this;
    }

    /**
     * @return Collection<int, KeywordRanking>
     */
    public function getRankings(): Collection
    {
        return $this->rankings;
    }

    public function addRanking(KeywordRanking $ranking): static
    {
        if (!$this->rankings->contains($ranking)) {
            $this->rankings->add($ranking);
            $ranking->setKeyword($this);
        }

        return $this;
    }

    public function removeRanking(KeywordRanking $ranking): static
    {
        if ($this->rankings->removeElement($ranking)) {
            // set the owning side to null (unless already changed)
            if ($ranking->getKeyword() === $this) {
                $ranking->setKeyword(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Competitor>
     */
    public function getCompetitors(): Collection
    {
        return $this->competitors;
    }

    public function addCompetitor(Competitor $competitor): static
    {
        if (!$this->competitors->contains($competitor)) {
            $this->competitors->add($competitor);
            $competitor->setKeyword($this);
        }

        return $this;
    }

    public function removeCompetitor(Competitor $competitor): static
    {
        if ($this->competitors->removeElement($competitor)) {
            // set the owning side to null (unless already changed)
            if ($competitor->getKeyword() === $this) {
                $competitor->setKeyword(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Alert>
     */
    public function getAlerts(): Collection
    {
        return $this->alerts;
    }

    public function addAlert(Alert $alert): static
    {
        if (!$this->alerts->contains($alert)) {
            $this->alerts->add($alert);
            $alert->setKeyword($this);
        }

        return $this;
    }

    public function removeAlert(Alert $alert): static
    {
        if ($this->alerts->removeElement($alert)) {
            // set the owning side to null (unless already changed)
            if ($alert->getKeyword() === $this) {
                $alert->setKeyword(null);
            }
        }

        return $this;
    }
}
