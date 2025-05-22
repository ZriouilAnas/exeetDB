<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    public function save(Produit $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Produit $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Produit[] Returns an array of Produit objects
     */
    public function findByCategorie(string $categorie): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.categorie = :val')
            ->setParameter('val', $categorie)
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des produits avec filtres multiples
     * 
     * @param array $filters
     * @return Produit[]
     */
    public function findWithFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('p');

        // Filtre par catégorie
        if (!empty($filters['categorie'])) {
            $qb->andWhere('p.categorie = :categorie')
               ->setParameter('categorie', $filters['categorie']);
        }

        // Filtre par prix minimum
        if (!empty($filters['prix_min']) && is_numeric($filters['prix_min'])) {
            $qb->andWhere('p.prix >= :prix_min')
               ->setParameter('prix_min', (float) $filters['prix_min']);
        }

        // Filtre par prix maximum
        if (!empty($filters['prix_max']) && is_numeric($filters['prix_max'])) {
            $qb->andWhere('p.prix <= :prix_max')
               ->setParameter('prix_max', (float) $filters['prix_max']);
        }

        // Filtre par nom (recherche partielle)
        if (!empty($filters['nom'])) {
            $qb->andWhere('p.nom LIKE :nom')
               ->setParameter('nom', '%' . $filters['nom'] . '%');
        }

        // Filtre par taille
        if (!empty($filters['taille'])) {
            $qb->andWhere('p.taille = :taille')
               ->setParameter('taille', $filters['taille']);
        }

        // Filtre par couleur
        if (!empty($filters['couleur'])) {
            $qb->andWhere('p.couleur LIKE :couleur')
               ->setParameter('couleur', '%' . $filters['couleur'] . '%');
        }

        // Filtre par sexe
        if (!empty($filters['sexe'])) {
            $qb->andWhere('p.sexe = :sexe')
               ->setParameter('sexe', $filters['sexe']);
        }

        // Tri
        $orderBy = $filters['order_by'] ?? 'nom';
        $orderDirection = $filters['order_direction'] ?? 'ASC';
        
        $allowedOrderBy = ['nom', 'prix', 'categorie', 'createdAt', 'updatedAt'];
        $allowedDirection = ['ASC', 'DESC'];
        
        if (in_array($orderBy, $allowedOrderBy) && in_array(strtoupper($orderDirection), $allowedDirection)) {
            $qb->orderBy('p.' . $orderBy, strtoupper($orderDirection));
        } else {
            $qb->orderBy('p.nom', 'ASC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche des produits par terme de recherche
     * 
     * @param string $terme
     * @return Produit[]
     */
    public function rechercherProduits(string $terme): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.nom LIKE :terme')
            ->orWhere('p.description LIKE :terme')
            ->orWhere('p.categorie LIKE :terme')
            ->setParameter('terme', '%' . $terme . '%')
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les produits dans une gamme de prix
     * 
     * @param float $prixMin
     * @param float $prixMax
     * @return Produit[]
     */
    public function findByPriceRange(float $prixMin, float $prixMax): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.prix BETWEEN :prix_min AND :prix_max')
            ->setParameter('prix_min', $prixMin)
            ->setParameter('prix_max', $prixMax)
            ->orderBy('p.prix', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les derniers produits ajoutés
     * 
     * @param int $limit
     * @return Produit[]
     */
    public function findLatest(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de produits par catégorie
     * 
     * @return array
     */
    public function countByCategorie(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.categorie, COUNT(p.id) as count')
            ->groupBy('p.categorie')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les produits similaires (même catégorie, prix proche)
     * 
     * @param Produit $produit
     * @param int $limit
     * @return Produit[]
     */
    public function findSimilar(Produit $produit, int $limit = 5): array
    {
        $prixMin = $produit->getPrix() * 0.7; // 30% moins cher
        $prixMax = $produit->getPrix() * 1.3; // 30% plus cher

        return $this->createQueryBuilder('p')
            ->where('p.categorie = :categorie')
            ->andWhere('p.prix BETWEEN :prix_min AND :prix_max')
            ->andWhere('p.id != :current_id')
            ->setParameter('categorie', $produit->getCategorie())
            ->setParameter('prix_min', $prixMin)
            ->setParameter('prix_max', $prixMax)
            ->setParameter('current_id', $produit->getId())
            ->orderBy('ABS(p.prix - :prix_reference)', 'ASC')
            ->setParameter('prix_reference', $produit->getPrix())
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des produits
     * 
     * @return array
     */
    public function getStatistiques(): array
    {
        $qb = $this->createQueryBuilder('p');
        
        $total = (int) $qb->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
        $prixMoyen = (float) $qb->select('AVG(p.prix)')->getQuery()->getSingleScalarResult();
        $prixMin = (float) $qb->select('MIN(p.prix)')->getQuery()->getSingleScalarResult();
        $prixMax = (float) $qb->select('MAX(p.prix)')->getQuery()->getSingleScalarResult();
        
        return [
            'total_produits' => $total,
            'prix_moyen' => round($prixMoyen, 2),
            'prix_minimum' => $prixMin,
            'prix_maximum' => $prixMax,
            'categories' => $this->countByCategorie()
        ];
    }
}