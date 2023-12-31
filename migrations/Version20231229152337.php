<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231229152337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE debtors (id INT UNSIGNED AUTO_INCREMENT NOT NULL, currency_id INT UNSIGNED DEFAULT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_2A8D8D6838248176 (currency_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payments (id INT UNSIGNED AUTO_INCREMENT NOT NULL, sum INT NOT NULL, note VARCHAR(255) DEFAULT NULL, description VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE debtors ADD CONSTRAINT FK_2A8D8D6838248176 FOREIGN KEY (currency_id) REFERENCES currency (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE payments ADD debtor_id INT UNSIGNED DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B32B043EC6B FOREIGN KEY (debtor_id) REFERENCES debtors (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_65D29B32B043EC6B ON payments (debtor_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B32B043EC6B');
        $this->addSql('DROP INDEX IDX_65D29B32B043EC6B ON payments');
        $this->addSql('ALTER TABLE payments DROP debtor_id, CHANGE description description VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE debtors DROP FOREIGN KEY FK_2A8D8D6838248176');
        $this->addSql('DROP TABLE debtors');
        $this->addSql('DROP TABLE payments');
    }
}
