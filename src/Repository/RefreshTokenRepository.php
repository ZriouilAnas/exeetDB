<?php

namespace App\Repository;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 */
class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function save(RefreshToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RefreshToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve un refresh token valide par sa valeur
     */
    public function findValidToken(string $token): ?RefreshToken
    {
        return $this->createQueryBuilder('rt')
            ->andWhere('rt.token = :token')
            ->andWhere('rt.isRevoked = false')
            ->andWhere('rt.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve tous les refresh tokens d'un utilisateur
     * 
     * @return RefreshToken[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('rt')
            ->andWhere('rt.user = :user')
            ->andWhere('rt.isRevoked = false')
            ->andWhere('rt.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('rt.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Révoque tous les refresh tokens d'un utilisateur
     */
    public function revokeAllUserTokens(User $user): int
    {
        return $this->createQueryBuilder('rt')
            ->update()
            ->set('rt.isRevoked', true)
            ->andWhere('rt.user = :user')
            ->andWhere('rt.isRevoked = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Révoque un token spécifique
     */
    public function revokeToken(string $token): bool
    {
        $result = $this->createQueryBuilder('rt')
            ->update()
            ->set('rt.isRevoked', true)
            ->andWhere('rt.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->execute();

        return $result > 0;
    }

    /**
     * Supprime les tokens expirés (à utiliser dans une commande de nettoyage)
     */
    public function deleteExpiredTokens(): int
    {
        return $this->createQueryBuilder('rt')
            ->delete()
            ->andWhere('rt.expiresAt < :now')
            ->orWhere('rt.isRevoked = true')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Compte les tokens actifs d'un utilisateur
     */
    public function countActiveTokensByUser(User $user): int
    {
        return $this->createQueryBuilder('rt')
            ->select('COUNT(rt.id)')
            ->andWhere('rt.user = :user')
            ->andWhere('rt.isRevoked = false')
            ->andWhere('rt.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les tokens par adresse IP (sécurité)
     * 
     * @return RefreshToken[]
     */
    public function findByIpAddress(string $ipAddress, int $limit = 10): array
    {
        return $this->createQueryBuilder('rt')
            ->andWhere('rt.ipAddress = :ip')
            ->andWhere('rt.createdAt > :since')
            ->setParameter('ip', $ipAddress)
            ->setParameter('since', new \DateTimeImmutable('-1 day'))
            ->orderBy('rt.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des refresh tokens
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('rt');
        
        $total = (int) $qb->select('COUNT(rt.id)')->getQuery()->getSingleScalarResult();
        
        $active = (int) $qb->select('COUNT(rt.id)')
            ->andWhere('rt.isRevoked = false')
            ->andWhere('rt.expiresAt > :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
        
        $expired = (int) $qb->select('COUNT(rt.id)')
            ->andWhere('rt.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
        
        $revoked = (int) $qb->select('COUNT(rt.id)')
            ->andWhere('rt.isRevoked = true')
            ->getQuery()
            ->getSingleScalarResult();
        
        return [
            'total' => $total,
            'active' => $active,
            'expired' => $expired,
            'revoked' => $revoked,
            'usage_rate' => $total > 0 ? round(($active / $total) * 100, 2) : 0
        ];
    }
}