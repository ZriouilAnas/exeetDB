<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[OA\Schema(
    description: 'Entité représentant un produit dans le catalogue',
    type: 'object',
    title: 'Produit'
)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["produit:read"])]
    #[OA\Property(
        description: 'Identifiant unique du produit',
        type: 'integer',
        example: 1
    )]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["produit:read", "produit:write"])]
    #[Assert\NotBlank(message: "Le nom du produit est obligatoire")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Type(type: "string", message: "Le nom doit être une chaîne de caractères")]
    #[OA\Property(
        description: 'Nom du produit',
        type: 'string',
        minLength: 2,
        maxLength: 255,
        example: 'T-shirt Nike Dri-FIT'
    )]
    private ?string $nom = null;

    #[ORM\Column(type: "text", nullable: true)]
    #[Groups(["produit:read", "produit:write"])]
    #[Assert\Length(
        max: 2000,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Type(type: "string", message: "La description doit être une chaîne de caractères")]
    #[OA\Property(
        description: 'Description détaillée du produit',
        type: 'string',
        maxLength: 2000,
        nullable: true,
        example: 'T-shirt de sport respirant pour homme, parfait pour les activités sportives'
    )]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(["produit:read", "produit:write"])]
    #[Assert\NotBlank(message: "Le prix est obligatoire")]
    #[Assert\Positive(message: "Le prix doit être positif")]
    #[Assert\LessThan(
        value: 999999.99,
        message: "Le prix ne peut pas dépasser {{ compared_value }} €"
    )]
    #[Assert\Type(type: "numeric", message: "Le prix doit être un nombre")]
    #[OA\Property(
        description: 'Prix du produit en euros',
        type: 'number',
        format: 'float',
        minimum: 0.01,
        maximum: 999999.99,
        example: 29.99
    )]
    private ?float $prix = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["produit:read", "produit:write"])]
    #[Assert\Url(message: "L'URL de l'image n'est pas valide", requireTld: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "L'URL de l'image ne peut pas dépasser {{ limit }} caractères"
    )]
    #[OA\Property(
        description: 'URL de l\'image principale du produit',
        type: 'string',
        format: 'url',
        maxLength: 255,
        nullable: true,
        example: 'https://example.com/images/tshirt-nike.jpg'
    )]
    private ?string $image = null;

    #[ORM\Column(length: 100)]
    #[Groups(["produit:read", "produit:write"])]
    #[Assert\NotBlank(message: "La catégorie est obligatoire")]
    #[Assert\Choice(
        choices: ['vetements', 'chaussures', 'accessoires', 'sport', 'electronique', 'maison', 'beaute', 'autres'],
        message: "La catégorie doit être l'une des suivantes : {{ choices }}"
    )]
    #[OA\Property(
        description: 'Catégorie du produit',
        type: 'string',
        enum: ['vetements', 'chaussures', 'accessoires', 'sport', 'electronique', 'maison', 'beaute', 'autres'],
        example: 'vetements'
    )]
    private ?string $categorie = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(["produit:read", "produit:write"])]
    #[Assert\Choice(
        choices: ['XS', 'S', 'M', 'L', 'XL', 'XXL', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'],
        message: "La taille doit être une taille valide (XS, S, M, L, XL, XXL ou pointure 36-46)"
    )]
    #[OA\Property(
        description: 'Taille du produit (vêtements: XS-XXL, chaussures: 36-46)',
        type: 'string',
        enum: ['XS', 'S', 'M', 'L', 'XL', 'XXL', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'],
        nullable: true,
        example: 'M'
    )]
    private ?string $taille = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(["produit:read", "produit:write"])]
    #[Assert\Length(
        max: 50,
        maxMessage: "La couleur ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ\s\-]+$/',
        message: "La couleur ne peut contenir que des lettres, espaces et tirets"
    )]
    #[OA\Property(
        description: 'Couleur principale du produit',
        type: 'string',
        maxLength: 50,
        pattern: '^[a-zA-ZÀ-ÿ\s\-]+',
        nullable: true,
        example: 'bleu marine'
    )]
    private ?string $couleur = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(["produit:read", "produit:write"])]
    #[Assert\Choice(
        choices: ['homme', 'femme', 'enfant', 'unisexe'],
        message: "Le sexe doit être : homme, femme, enfant ou unisexe"
    )]
    #[OA\Property(
        description: 'Public cible du produit',
        type: 'string',
        enum: ['homme', 'femme', 'enfant', 'unisexe'],
        nullable: true,
        example: 'homme'
    )]
    private ?string $sexe = null;

    #[ORM\Column]
    #[Groups(["produit:read"])]
    #[OA\Property(
        description: 'Date et heure de création du produit',
        type: 'string',
        format: 'date-time',
        example: '2025-01-22T10:30:00+00:00'
    )]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(["produit:read"])]
    #[OA\Property(
        description: 'Date et heure de dernière modification du produit',
        type: 'string',
        format: 'date-time',
        example: '2025-01-22T14:45:00+00:00'
    )]
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
        $this->nom = trim($nom); // Supprime les espaces en début/fin
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description ? trim($description) : null;
        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = round($prix, 2); // Arrondi à 2 décimales
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image ? trim($image) : null;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = strtolower(trim($categorie)); // Normalise en minuscules
        return $this;
    }

    public function getTaille(): ?string
    {
        return $this->taille;
    }

    public function setTaille(?string $taille): static
    {
        $this->taille = $taille ? strtoupper(trim($taille)) : null; // Normalise en majuscules
        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): static
    {
        $this->couleur = $couleur ? strtolower(trim($couleur)) : null; // Normalise en minuscules
        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): static
    {
        $this->sexe = $sexe ? strtolower(trim($sexe)) : null; // Normalise en minuscules
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

    /**
     * Méthode utilitaire pour obtenir le prix formaté
     */
    #[OA\Property(
        description: 'Prix formaté avec devise (lecture seule)',
        type: 'string',
        example: '29,99 €'
    )]
    public function getPrixFormate(): string
    {
        return number_format($this->prix, 2, ',', ' ') . ' €';
    }

    /**
     * Vérifie si le produit est en stock (exemple pour extension future)
     */
    #[OA\Property(
        description: 'Statut de disponibilité du produit (lecture seule)',
        type: 'boolean',
        example: true
    )]
    public function estDisponible(): bool
    {
        // Logique à implémenter selon vos besoins
        return true;
    }
}