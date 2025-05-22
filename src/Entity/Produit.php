<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["produit:read"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["produit:read", "produit:write"])]
    private ?string $nom = null;

    #[ORM\Column(type: "text", nullable: true)]
    #[Groups(["produit:read", "produit:write"])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(["produit:read", "produit:write"])]
    private ?float $prix = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["produit:read", "produit:write"])]
    private ?string $image = null;

    #[ORM\Column(length: 100)]
    #[Groups(["produit:read", "produit:write"])]
    private ?string $categorie = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(["produit:read", "produit:write"])]
    private ?string $taille = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(["produit:read", "produit:write"])]
    private ?string $couleur = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(["produit:read", "produit:write"])]
    private ?string $sexe = null;

    #[ORM\Column]
    #[Groups(["produit:read"])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(["produit:read"])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
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

    public function getTaille(): ?string
    {
        return $this->taille;
    }

    public function setTaille(?string $taille): static
    {
        $this->taille = $taille;
        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): static
    {
        $this->couleur = $couleur;
        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): static
    {
        $this->sexe = $sexe;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PreUpdate]
    public function mettreAJourDateModification(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}