<?php

namespace App\Entity;

use App\Repository\SortieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SortieRepository::class)]
class Sortie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomSortie = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column]
    private ?int $duree = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateCloture = null;

    #[ORM\Column]
    private ?int $nbInscriptionMax = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $descriptionInfos = null;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    private ?Lieu $lieu = null;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    private ?Etat $etat = null;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    private ?Campus $campus = null;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    private ?Participant $participant = null;

    /**
     * @var Collection<int, Inscription>
     */
    #[ORM\OneToMany(targetEntity: Inscription::class, mappedBy: 'sortie')]
    private Collection $inscriptions;

    #[ORM\Column(length: 255)]
    private ?string $categorie = null;

    public function __construct()
    {
        $this->inscriptions = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomSortie(): ?string
    {
        return $this->nomSortie;
    }

    public function setNomSortie(string $nomSortie): static
    {
        $this->nomSortie = $nomSortie;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(int $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    public function getDateCloture(): ?\DateTimeImmutable
    {
        return $this->dateCloture;
    }

    public function setDateCloture(\DateTimeImmutable $dateCloture): static
    {
        $this->dateCloture = $dateCloture;

        return $this;
    }

    public function getNbInscriptionMax(): ?int
    {
        return $this->nbInscriptionMax;
    }

    public function setNbInscriptionMax(int $nbInscriptionMax): static
    {
        $this->nbInscriptionMax = $nbInscriptionMax;

        return $this;
    }

    public function getDescriptionInfos(): ?string
    {
        return $this->descriptionInfos;
    }

    public function setDescriptionInfos(string $descriptionInfos): static
    {
        $this->descriptionInfos = $descriptionInfos;

        return $this;
    }

    public function getLieu(): ?Lieu
    {
        return $this->lieu;
    }

    public function setLieu(?Lieu $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getEtat(): ?Etat
    {
        return $this->etat;
    }

    public function setEtat(?Etat $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getCampus(): ?Campus
    {
        return $this->campus;
    }

    public function setCampus(?Campus $campus): static
    {
        $this->campus = $campus;

        return $this;
    }

    public function getParticipant(): ?Participant
    {
        return $this->participant;
    }

    public function setParticipant(?Participant $participant): static
    {
        $this->participant = $participant;

        return $this;
    }

    /**
     * @return Collection<int, Inscription>
     */
    public function getInscriptions(): Collection
    {
        return $this->inscriptions;
    }

    public function addInscription(Inscription $inscription): static
    {
        if (!$this->inscriptions->contains($inscription)) {
            $this->inscriptions->add($inscription);
            $inscription->setSortie($this);
        }

        return $this;
    }

    public function removeInscription(Inscription $inscription): static
    {
        if ($this->inscriptions->removeElement($inscription)) {
            // set the owning side to null (unless already changed)
            if ($inscription->getSortie() === $this) {
                $inscription->setSortie(null);
            }
        }

        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

}
