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

#[Route('/api/produits')]
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
    public function listerProduits(Request $request): JsonResponse
    {
        try {
            // Paramètres de filtrage optionnels
            $categorie = $request->query->get('categorie');
            $prixMin = $request->query->get('prix_min');
            $prixMax = $request->query->get('prix_max');
            
            if ($categorie || $prixMin || $prixMax) {
                $produits = $this->produitRepository->findWithFilters([
                    'categorie' => $categorie,
                    'prix_min' => $prixMin,
                    'prix_max' => $prixMax
                ]);
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