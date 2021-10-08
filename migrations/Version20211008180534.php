<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211008180534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product ADD purchase_price INT NOT NULL, ADD delivery_cost INT NOT NULL, ADD note LONGTEXT NOT NULL');
        $this->addSql('CREATE TABLE currency (id INT UNSIGNED AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, exchange_rate DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product ADD currency_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD38248176 FOREIGN KEY (currency_id) REFERENCES currency (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_D34A04AD38248176 ON product (currency_id)');
        $this->addSql('ALTER TABLE currency CHANGE exchange_rate exchange_rate NUMERIC(10, 2) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP purchase_price, DROP delivery_cost, DROP note');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD38248176');
        $this->addSql('DROP TABLE currency');
        $this->addSql('DROP INDEX IDX_D34A04AD38248176 ON product');
        $this->addSql('ALTER TABLE product DROP currency_id');
        $this->addSql('ALTER TABLE currency CHANGE exchange_rate exchange_rate DOUBLE PRECISION NOT NULL');
    }
}
