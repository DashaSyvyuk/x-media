<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220509191244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE delivery_type (id INT UNSIGNED AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, cost VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE delivery_payment (delivery_type_id INT UNSIGNED NOT NULL, payment_type_id INT UNSIGNED NOT NULL, INDEX IDX_67363BD3CF52334D (delivery_type_id), INDEX IDX_67363BD3DC058279 (payment_type_id), PRIMARY KEY(delivery_type_id, payment_type_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment_type (id INT UNSIGNED AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE delivery_payment ADD CONSTRAINT FK_67363BD3CF52334D FOREIGN KEY (delivery_type_id) REFERENCES delivery_type (id)');
        $this->addSql('ALTER TABLE delivery_payment ADD CONSTRAINT FK_67363BD3DC058279 FOREIGN KEY (payment_type_id) REFERENCES payment_type (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE delivery_payment DROP FOREIGN KEY FK_67363BD3CF52334D');
        $this->addSql('ALTER TABLE delivery_payment DROP FOREIGN KEY FK_67363BD3DC058279');
        $this->addSql('DROP TABLE delivery_type');
        $this->addSql('DROP TABLE delivery_payment');
        $this->addSql('DROP TABLE payment_type');
    }
}
