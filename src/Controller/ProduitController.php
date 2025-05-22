<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;

#[Route('/api/produits')]
#[OA\Tag(name: 'Produits', description: 'Gestion des produits')]
class ProduitController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ProduitRepository $produitRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProduitRepository $produitRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->produitRepository = $produitRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    #[Route('', name: 'produit_liste', methods: ['GET'])]
    #[OA\Get(
        path: '/api/produits',
        summary: 'Liste tous les produits',
        description: 'Récupère la liste complète des produits avec possibilité de filtrage',
        tags: ['Produits']
    )]
    #[OA\Parameter(
        name: 'categorie',
        description: 'Filtrer par catégorie',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['vetements', 'chaussures', 'accessoires', 'sport', 'electronique', 'maison', 'beaute', 'autres'])
    )]
    #[OA\Parameter(
        name: 'prix_min',
        description: 'Prix minimum',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'number', format: 'float', minimum: 0)
    )]
    #[OA\Parameter(
        name: 'prix_max',
        description: 'Prix maximum',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'number', format: 'float', maximum: 999999.99)
    )]
    #[OA\Parameter(
        name: 'nom',
        description: 'Recherche partielle dans le nom',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'taille',
        description: 'Filtrer par taille',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['XS', 'S', 'M', 'L', 'XL', 'XXL', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'])
    )]
    #[OA\Parameter(
        name: 'couleur',
        description: 'Recherche partielle dans la couleur',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'sexe',
        description: 'Filtrer par sexe',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['homme', 'femme', 'enfant', 'unisexe'])
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste des produits récupérée avec succès',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(
                    property: 'data', 
                    type: 'array', 
                    items: new OA\Items(ref: new Model(type: Produit::class, groups: ['produit:read']))
                ),
                new OA\Property(property: 'count', type: 'integer', example: 15)
            ]
        )
    )]
    #[OA\Response(
        response: 500,
        description: 'Erreur serveur',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Erreur lors de la récupération des produits'),
                new OA\Property(property: 'error', type: 'string', example: 'Message d\'erreur détaillé')
            ]
        )
    )]
    public function listerProduits(Request $request): JsonResponse
    {
        try {
            // Paramètres de filtrage optionnels
            $categorie = $request->query->get('categorie');
            $prixMin = $request->query->get('prix_min');
            $prixMax = $request->query->get('prix_max');
            $nom = $request->query->get('nom');
            $taille = $request->query->get('taille');
            $couleur = $request->query->get('couleur');
            $sexe = $request->query->get('sexe');
            
            $filters = array_filter([
                'categorie' => $categorie,
                'prix_min' => $prixMin,
                'prix_max' => $prixMax,
                'nom' => $nom,
                'taille' => $taille,
                'couleur' => $couleur,
                'sexe' => $sexe
            ]);
            
            if (!empty($filters)) {
                $produits = $this->produitRepository->findWithFilters($filters);
            } else {
                $produits = $this->produitRepository->findAll();
            }
            
            return $this->json([
                'success' => true,
                'data' => $produits,
                'count' => count($produits)
            ], Response::HTTP_OK, [], ['groups' => 'produit:read']);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des produits',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'produit_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[OA\Get(
        path: '/api/produits/{id}',
        summary: 'Récupère un produit par son ID',
        description: 'Affiche les détails complets d\'un produit spécifique',
        tags: ['Produits']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID du produit',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', minimum: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Produit trouvé',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', ref: new Model(type: Produit::class, groups: ['produit:read']))
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Produit non trouvé',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Produit non trouvé')
            ]
        )
    )]
    public function detailProduit(int $id): JsonResponse
    {
        try {
            $produit = $this->produitRepository->find($id);
            
            if (!$produit) {
                return $this->json([
                    'success' => false,
                    'message' => 'Produit non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }
            
            return $this->json([
                'success' => true,
                'data' => $produit
            ], Response::HTTP_OK, [], ['groups' => 'produit:read']);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du produit',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'produit_creer', methods: ['POST'])]
    #[OA\Post(
        path: '/api/produits',
        summary: 'Crée un nouveau produit',
        description: 'Ajoute un nouveau produit dans la base de données avec validation complète',
        tags: ['Produits']
    )]
    #[OA\RequestBody(
        description: 'Données du produit à créer',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['nom', 'prix', 'categorie'],
            properties: [
                new OA\Property(property: 'nom', type: 'string', minLength: 2, maxLength: 255, example: 'T-shirt Nike Dri-FIT'),
                new OA\Property(property: 'description', type: 'string', maxLength: 2000, example: 'T-shirt de sport respirant pour homme'),
                new OA\Property(property: 'prix', type: 'number', format: 'float', minimum: 0.01, maximum: 999999.99, example: 29.99),
                new OA\Property(property: 'image', type: 'string', format: 'url', maxLength: 255, example: 'https://example.com/tshirt.jpg'),
                new OA\Property(property: 'categorie', type: 'string', enum: ['vetements', 'chaussures', 'accessoires', 'sport', 'electronique', 'maison', 'beaute', 'autres'], example: 'vetements'),
                new OA\Property(property: 'taille', type: 'string', enum: ['XS', 'S', 'M', 'L', 'XL', 'XXL', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'], example: 'M'),
                new OA\Property(property: 'couleur', type: 'string', maxLength: 50, pattern: '^[a-zA-ZÀ-ÿ\s\-]+$', example: 'bleu marine'),
                new OA\Property(property: 'sexe', type: 'string', enum: ['homme', 'femme', 'enfant', 'unisexe'], example: 'homme')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Produit créé avec succès',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Produit créé avec succès'),
                new OA\Property(property: 'data', ref: new Model(type: Produit::class, groups: ['produit:read']))
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Erreur de validation',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Données de validation invalides'),
                new OA\Property(
                    property: 'errors', 
                    type: 'object',
                    example: ['nom' => 'Le nom du produit est obligatoire', 'prix' => 'Le prix doit être positif']
                )
            ]
        )
    )]
    public function creerProduit(Request $request): JsonResponse
    {
        try {
            // Vérification du Content-Type
            if (!$request->headers->contains('Content-Type', 'application/json')) {
                return $this->json([
                    'success' => false,
                    'message' => 'Content-Type doit être application/json'
                ], Response::HTTP_BAD_REQUEST);
            }

            $contenu = $request->getContent();
            if (empty($contenu)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Corps de la requête vide'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Décodage JSON avec gestion d'erreur
            $donnees = json_decode($contenu, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'success' => false,
                    'message' => 'Format JSON invalide',
                    'error' => json_last_error_msg()
                ], Response::HTTP_BAD_REQUEST);
            }

            // Création et hydratation de l'entité
            $produit = new Produit();
            $this->hydraterProduit($produit, $donnees);
            
            // Validation
            $erreurs = $this->validator->validate($produit);
            if (count($erreurs) > 0) {
                $erreursFormatees = $this->formaterErreursValidation($erreurs);
                return $this->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $erreursFormatees
                ], Response::HTTP_BAD_REQUEST);
            }
            
            // Sauvegarde
            $this->entityManager->persist($produit);
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Produit créé avec succès',
                'data' => $produit
            ], Response::HTTP_CREATED, [], ['groups' => 'produit:read']);
            
        } catch (NotEncodableValueException $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur de format des données',
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création du produit',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'produit_modifier', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[OA\Put(
        path: '/api/produits/{id}',
        summary: 'Modifie un produit existant',
        description: 'Met à jour les données d\'un produit. Seuls les champs fournis seront modifiés.',
        tags: ['Produits']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID du produit à modifier',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', minimum: 1)
    )]
    #[OA\RequestBody(
        description: 'Données du produit à modifier (seuls les champs à changer)',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'nom', type: 'string', minLength: 2, maxLength: 255, example: 'T-shirt Nike Dri-FIT Modifié'),
                new OA\Property(property: 'description', type: 'string', maxLength: 2000, example: 'Description mise à jour'),
                new OA\Property(property: 'prix', type: 'number', format: 'float', minimum: 0.01, maximum: 999999.99, example: 24.99),
                new OA\Property(property: 'image', type: 'string', format: 'url', maxLength: 255, example: 'https://example.com/new-image.jpg'),
                new OA\Property(property: 'categorie', type: 'string', enum: ['vetements', 'chaussures', 'accessoires', 'sport', 'electronique', 'maison', 'beaute', 'autres'], example: 'sport'),
                new OA\Property(property: 'taille', type: 'string', enum: ['XS', 'S', 'M', 'L', 'XL', 'XXL', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'], example: 'L'),
                new OA\Property(property: 'couleur', type: 'string', maxLength: 50, pattern: '^[a-zA-ZÀ-ÿ\s\-]+$', example: 'rouge'),
                new OA\Property(property: 'sexe', type: 'string', enum: ['homme', 'femme', 'enfant', 'unisexe'], example: 'femme')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Produit modifié avec succès',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Produit modifié avec succès'),
                new OA\Property(property: 'data', ref: new Model(type: Produit::class, groups: ['produit:read']))
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Produit non trouvé',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Produit non trouvé')
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Erreur de validation',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Données de validation invalides'),
                new OA\Property(
                    property: 'errors', 
                    type: 'object',
                    example: ['prix' => 'Le prix doit être positif']
                )
            ]
        )
    )]
    public function modifierProduit(int $id, Request $request): JsonResponse
    {
        try {
            $produit = $this->produitRepository->find($id);
            
            if (!$produit) {
                return $this->json([
                    'success' => false,
                    'message' => 'Produit non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }
            
            $contenu = $request->getContent();
            if (empty($contenu)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Corps de la requête vide'
                ], Response::HTTP_BAD_REQUEST);
            }

            $donnees = json_decode($contenu, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'success' => false,
                    'message' => 'Format JSON invalide',
                    'error' => json_last_error_msg()
                ], Response::HTTP_BAD_REQUEST);
            }
            
            // Hydratation partielle (seuls les champs fournis)
            $this->hydraterProduit($produit, $donnees, false);
            
            // Validation
            $erreurs = $this->validator->validate($produit);
            if (count($erreurs) > 0) {
                $erreursFormatees = $this->formaterErreursValidation($erreurs);
                return $this->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $erreursFormatees
                ], Response::HTTP_BAD_REQUEST);
            }
            
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Produit modifié avec succès',
                'data' => $produit
            ], Response::HTTP_OK, [], ['groups' => 'produit:read']);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du produit',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'produit_supprimer', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[OA\Delete(
        path: '/api/produits/{id}',
        summary: 'Supprime un produit',
        description: 'Supprime définitivement un produit de la base de données',
        tags: ['Produits']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID du produit à supprimer',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', minimum: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Produit supprimé avec succès',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Produit supprimé avec succès')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Produit non trouvé',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Produit non trouvé')
            ]
        )
    )]
    public function supprimerProduit(int $id): JsonResponse
    {
        try {
            $produit = $this->produitRepository->find($id);
            
            if (!$produit) {
                return $this->json([
                    'success' => false,
                    'message' => 'Produit non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }
            
            $this->entityManager->remove($produit);
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Produit supprimé avec succès'
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du produit',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/categorie/{categorie}', name: 'produits_par_categorie', methods: ['GET'])]
    #[OA\Get(
        path: '/api/produits/categorie/{categorie}',
        summary: 'Récupère tous les produits d\'une catégorie',
        description: 'Affiche tous les produits appartenant à une catégorie spécifique',
        tags: ['Produits']
    )]
    #[OA\Parameter(
        name: 'categorie',
        description: 'Nom de la catégorie',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', enum: ['vetements', 'chaussures', 'accessoires', 'sport', 'electronique', 'maison', 'beaute', 'autres'])
    )]
    #[OA\Response(
        response: 200,
        description: 'Produits de la catégorie récupérés avec succès',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(
                    property: 'data', 
                    type: 'array', 
                    items: new OA\Items(ref: new Model(type: Produit::class, groups: ['produit:read']))
                ),
                new OA\Property(property: 'count', type: 'integer', example: 8),
                new OA\Property(property: 'categorie', type: 'string', example: 'vetements')
            ]
        )
    )]
    public function produitsByCategorie(string $categorie): JsonResponse
    {
        try {
            $produits = $this->produitRepository->findByCategorie($categorie);
            
            return $this->json([
                'success' => true,
                'data' => $produits,
                'count' => count($produits),
                'categorie' => $categorie
            ], Response::HTTP_OK, [], ['groups' => 'produit:read']);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des produits par catégorie',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Hydrate un produit avec les données fournies
     */
    private function hydraterProduit(Produit $produit, array $donnees, bool $creation = true): void
    {
        if (isset($donnees['nom']) || $creation) {
            $produit->setNom($donnees['nom'] ?? '');
        }
        
        if (isset($donnees['description'])) {
            $produit->setDescription($donnees['description']);
        }
        
        if (isset($donnees['prix']) || $creation) {
            $produit->setPrix($donnees['prix'] ?? 0);
        }
        
        if (isset($donnees['image'])) {
            $produit->setImage($donnees['image']);
        }
        
        if (isset($donnees['categorie']) || $creation) {
            $produit->setCategorie($donnees['categorie'] ?? '');
        }
        
        if (isset($donnees['taille'])) {
            $produit->setTaille($donnees['taille']);
        }
        
        if (isset($donnees['couleur'])) {
            $produit->setCouleur($donnees['couleur']);
        }
        
        if (isset($donnees['sexe'])) {
            $produit->setSexe($donnees['sexe']);
        }
    }

    /**
     * Formate les erreurs de validation pour l'API
     */
    private function formaterErreursValidation($erreurs): array
    {
        $erreursFormatees = [];
        foreach ($erreurs as $erreur) {
            $champ = $erreur->getPropertyPath();
            $erreursFormatees[$champ] = $erreur->getMessage();
        }
        return $erreursFormatees;
    }
}