<?php

namespace App\Entity;

use App\Repository\ClubRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClubRepository::class)]
class Club
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom_club = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo_club = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom_stade_club = null;

    #[ORM\Column(nullable: true)]
    private ?float $latitude_stade_club = null;

    #[ORM\Column(nullable: true)]
    private ?float $longitude_stade_club = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'stadiums_visited')]
    private Collection $visited_by;

    public function __construct()
    {
        $this->visited_by = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomClub(): ?string
    {
        return $this->nom_club;
    }

    public function setNomClub(?string $nom_club): static
    {
        $this->nom_club = $nom_club;

        return $this;
    }

    public function getLogoClub(): ?string
    {
        return $this->logo_club;
    }

    public function setLogoClub(?string $logo_club): static
    {
        $this->logo_club = $logo_club;

        return $this;
    }

    public function getNomStadeClub(): ?string
    {
        return $this->nom_stade_club;
    }

    public function setNomStadeClub(?string $nom_stade_club): static
    {
        $this->nom_stade_club = $nom_stade_club;

        return $this;
    }

    public function getLatitudeStadeClub(): ?float
    {
        return $this->latitude_stade_club;
    }

    public function setLatitudeStadeClub(?float $latitude_stade_club): static
    {
        $this->latitude_stade_club = $latitude_stade_club;

        return $this;
    }

    public function getLongitudeStadeClub(): ?float
    {
        return $this->longitude_stade_club;
    }

    public function setLongitudeStadeClub(?float $longitude_stade_club): static
    {
        $this->longitude_stade_club = $longitude_stade_club;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getVisitedBy(): Collection
    {
        return $this->visited_by;
    }

    public function addVisitedBy(User $visitedBy): static
    {
        if (!$this->visited_by->contains($visitedBy)) {
            $this->visited_by->add($visitedBy);
            $visitedBy->addStadiumsVisited($this);
        }

        return $this;
    }

    public function removeVisitedBy(User $visitedBy): static
    {
        if ($this->visited_by->removeElement($visitedBy)) {
            $visitedBy->removeStadiumsVisited($this);
        }

        return $this;
    }
}
