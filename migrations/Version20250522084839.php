<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250522084839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table produit avec tous les champs nécessaires';
    }

    public function up(Schema $schema): void
    {
        // Création de la table produit (nom cohérent avec l'entité)
        $this->addSql('CREATE TABLE produit (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            prix DECIMAL(10, 2) NOT NULL,
            image VARCHAR(255) DEFAULT NULL,
            categorie VARCHAR(100) NOT NULL,
            taille VARCHAR(10) DEFAULT NULL,
            couleur VARCHAR(50) DEFAULT NULL,
            sexe VARCHAR(20) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Index pour améliorer les performances des recherches
        $this->addSql('CREATE INDEX IDX_29A5EC27E9038C4F ON produit (nom)');
        $this->addSql('CREATE INDEX IDX_29A5EC27497DD634 ON produit (categorie)');
        $this->addSql('CREATE INDEX IDX_29A5EC27944722F ON produit (prix)');
    }

    public function down(Schema $schema): void
    {
        // Suppression des index
        $this->addSql('DROP INDEX IDX_29A5EC27E9038C4F ON produit');
        $this->addSql('DROP INDEX IDX_29A5EC27497DD634 ON produit');
        $this->addSql('DROP INDEX IDX_29A5EC27944722F ON produit');
        
        // Suppression de la table
        $this->addSql('DROP TABLE produit');
    }
}
