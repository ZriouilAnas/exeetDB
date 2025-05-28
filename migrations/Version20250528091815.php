<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250528091815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table refresh_token pour la gestion des tokens de renouvellement JWT';
    }

    public function up(Schema $schema): void
    {
        // Création de la table refresh_token
        $this->addSql('CREATE TABLE refresh_token (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            token VARCHAR(128) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            last_used_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(500) DEFAULT NULL,
            is_revoked TINYINT(1) NOT NULL DEFAULT 0,
            UNIQUE INDEX UNIQ_C74F21955F37A13B (token),
            INDEX IDX_C74F2195A76ED395 (user_id),
            INDEX idx_refresh_token (token),
            INDEX idx_expires_at (expires_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Clé étrangère vers la table user
        $this->addSql('ALTER TABLE refresh_token ADD CONSTRAINT FK_C74F2195A76ED395 
            FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Suppression de la contrainte de clé étrangère
        $this->addSql('ALTER TABLE refresh_token DROP FOREIGN KEY FK_C74F2195A76ED395');
        
        // Suppression de la table
        $this->addSql('DROP TABLE refresh_token');
    }
}
