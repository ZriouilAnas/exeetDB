<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration automatique pour créer la table des produits
 */
final class Version20250424124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table des produits avec tous les champs nécessaires';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE products (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            prix DECIMAL(10, 2) NOT NULL,
            image VARCHAR(255) NOT NULL,
            categorie VARCHAR(255) NOT NULL,
            taille VARCHAR(10) NOT NULL,
            couleur VARCHAR(50) NOT NULL,
            sexe VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE products');
    }
}