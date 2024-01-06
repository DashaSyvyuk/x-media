<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240106201401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE circulation_payments (id INT UNSIGNED AUTO_INCREMENT NOT NULL, circulation_id INT UNSIGNED DEFAULT NULL, sum INT NOT NULL, note VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_BFDE2E77117A6BC (circulation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE circulations (id INT UNSIGNED AUTO_INCREMENT NOT NULL, admin_user_id INT DEFAULT NULL, currency_id INT UNSIGNED DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_C85949406352511C (admin_user_id), INDEX IDX_C859494038248176 (currency_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE circulation_payments ADD CONSTRAINT FK_BFDE2E77117A6BC FOREIGN KEY (circulation_id) REFERENCES circulations (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE circulations ADD CONSTRAINT FK_C85949406352511C FOREIGN KEY (admin_user_id) REFERENCES admin_user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE circulations ADD CONSTRAINT FK_C859494038248176 FOREIGN KEY (currency_id) REFERENCES currency (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE payments RENAME debtor_payments;');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE debtor_payments RENAME payments;');
        $this->addSql('ALTER TABLE circulation_payments DROP FOREIGN KEY FK_BFDE2E77117A6BC');
        $this->addSql('ALTER TABLE circulations DROP FOREIGN KEY FK_C85949406352511C');
        $this->addSql('ALTER TABLE circulations DROP FOREIGN KEY FK_C859494038248176');
        $this->addSql('DROP TABLE circulation_payments');
        $this->addSql('DROP TABLE circulations');
    }
}
