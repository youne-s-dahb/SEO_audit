<?php

namespace App\Entity;

use App\Repository\AlertRepository;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlertRepository::class)]
#[ORM\Table(name: 'alerts')] // Smiya dial l-table f DB dyalk
#[ApiResource]              // 2. Zid had l-khatem s-s7ri hna 🔥
class Alert
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
