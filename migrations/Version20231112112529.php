<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231112112529 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE promotion_product (id INT UNSIGNED AUTO_INCREMENT NOT NULL, product_id INT UNSIGNED NOT NULL, promotion_id INT UNSIGNED NOT NULL, INDEX IDX_8B37F2974584665A (product_id), INDEX IDX_8B37F297139DF194 (promotion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE promotions (id INT UNSIGNED AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, active_from DATETIME NOT NULL, active_to DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE promotion_product ADD CONSTRAINT FK_8B37F2974584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE promotion_product ADD CONSTRAINT FK_8B37F297139DF194 FOREIGN KEY (promotion_id) REFERENCES promotions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE slider ADD promotion_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE slider ADD CONSTRAINT FK_CFC71007139DF194 FOREIGN KEY (promotion_id) REFERENCES promotions (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_CFC71007139DF194 ON slider (promotion_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE slider DROP FOREIGN KEY FK_CFC71007139DF194');
        $this->addSql('ALTER TABLE promotion_product DROP FOREIGN KEY FK_8B37F2974584665A');
        $this->addSql('ALTER TABLE promotion_product DROP FOREIGN KEY FK_8B37F297139DF194');
        $this->addSql('DROP TABLE promotion_product');
        $this->addSql('DROP TABLE promotions');
        $this->addSql('DROP INDEX IDX_CFC71007139DF194 ON slider');
        $this->addSql('ALTER TABLE slider DROP promotion_id');
    }
}
