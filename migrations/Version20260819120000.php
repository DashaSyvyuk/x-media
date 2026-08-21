<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260819120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cards and card_operations tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cards (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            holder_name VARCHAR(255) NOT NULL,
            card_number VARCHAR(19) NOT NULL,
            expiry VARCHAR(5) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_CARDS_TITLE (title),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE card_operations (
            id INT UNSIGNED AUTO_INCREMENT NOT NULL,
            card_id INT UNSIGNED NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            operated_at DATETIME NOT NULL,
            is_done TINYINT(1) NOT NULL DEFAULT 0,
            note LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_CARD_OPS_CARD_DATE (card_id, operated_at),
            INDEX IDX_CARD_OPS_DONE (is_done),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE card_operations ADD CONSTRAINT FK_CARD_OPS_CARD FOREIGN KEY (card_id) REFERENCES cards (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE card_operations DROP FOREIGN KEY FK_CARD_OPS_CARD');
        $this->addSql('DROP TABLE card_operations');
        $this->addSql('DROP TABLE cards');
    }
}
