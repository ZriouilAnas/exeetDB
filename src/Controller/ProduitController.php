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

#[Route('/api/produits')]
class ProduitController extends AbstractController
{
    private $entityManager;
    private $produitRepository;
    private $serializer;
    private $validator;

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
    public function listerProduits(): JsonResponse
    {
        $produits = $this->produitRepository->findAll();
        
        return $this->json($produits, Response::HTTP_OK, [], ['groups' => 'produit:read']);
    }

    #[Route('/{id}', name: 'produit_detail', methods: ['GET'])]
    public function detailProduit(int $id): JsonResponse
    {
        $produit = $this->produitRepository->find($id);
        
        if (!$produit) {
            return $this->json(['message' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }
        
        return $this->json($produit, Response::HTTP_OK, [], ['groups' => 'produit:read']);
    }

    #[Route('', name: 'produit_creer', methods: ['POST'])]
    public function creerProduit(Request $request): JsonResponse
    {
        $donnees = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['message' => 'Format JSON invalide'], Response::HTTP_BAD_REQUEST);
        }
        
        $produit = new Produit();
        $produit->setNom($donnees['nom'] ?? '');
        $produit->setDescription($donnees['description'] ?? null);
        $produit->setPrix($donnees['prix'] ?? 0);
        $produit->setImage($donnees['image'] ?? null);
        $produit->setCategorie($donnees['categorie'] ?? '');
        $produit->setTaille($donnees['taille'] ?? null);
        $produit->setCouleur($donnees['couleur'] ?? null);
        $produit->setSexe($donnees['sexe'] ?? null);
        
        $erreurs = $this->validator->validate($produit);
        if (count($erreurs) > 0) {
            $erreurMessages = [];
            foreach ($erreurs as $erreur) {
                $erreurMessages[$erreur->getPropertyPath()] = $erreur->getMessage();
            }
            return $this->json(['erreurs' => $erreurMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->persist($produit);
        $this->entityManager->flush();
        
        return $this->json($produit, Response::HTTP_CREATED, [], ['groups' => 'produit:read']);
    }

    #[Route('/{id}', name: 'produit_modifier', methods: ['PUT'])]
    public function modifierProduit(int $id, Request $request): JsonResponse
    {
        $produit = $this->produitRepository->find($id);
        
        if (!$produit) {
            return $this->json(['message' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }
        
        $donnees = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['message' => 'Format JSON invalide'], Response::HTTP_BAD_REQUEST);
        }
        
        if (isset($donnees['nom'])) $produit->setNom($donnees['nom']);
        if (isset($donnees['description'])) $produit->setDescription($donnees['description']);
        if (isset($donnees['prix'])) $produit->setPrix($donnees['prix']);
        if (isset($donnees['image'])) $produit->setImage($donnees['image']);
        if (isset($donnees['categorie'])) $produit->setCategorie($donnees['categorie']);
        if (isset($donnees['taille'])) $produit->setTaille($donnees['taille']);
        if (isset($donnees['couleur'])) $produit->setCouleur($donnees['couleur']);
        if (isset($donnees['sexe'])) $produit->setSexe($donnees['sexe']);
        
        $this->entityManager->flush();
        
        return $this->json($produit, Response::HTTP_OK, [], ['groups' => 'produit:read']);
    }

    #[Route('/{id}', name: 'produit_supprimer', methods: ['DELETE'])]
    public function supprimerProduit(int $id): JsonResponse
    {
        $produit = $this->produitRepository->find($id);
        
        if (!$produit) {
            return $this->json(['message' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }
        
        $this->entityManager->remove($produit);
        $this->entityManager->flush();
        
        return $this->json(['message' => 'Produit supprimé avec succès'], Response::HTTP_OK);
    }

    #[Route('/categorie/{categorie}', name: 'produits_par_categorie', methods: ['GET'])]
    public function produitsByCategorie(string $categorie): JsonResponse
    {
        $produits = $this->produitRepository->findByCategorie($categorie);
        
        return $this->json($produits, Response::HTTP_OK, [], ['groups' => 'produit:read']);
    }
}