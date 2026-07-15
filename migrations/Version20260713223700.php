<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260713223700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add FOP profiles for invoice generation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE fop_profiles (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            bank_account VARCHAR(255) NOT NULL,
            edrpou VARCHAR(255) NOT NULL,
            address VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_FOP_PROFILES_TITLE (title),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE fop_profiles');
    }
}

