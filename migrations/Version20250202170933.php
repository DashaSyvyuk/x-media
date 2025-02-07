<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250202170933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12391873');
        $this->addSql('DROP INDEX UNIQ_D34A04AD12391873 ON product');
        $this->addSql('ALTER TABLE product DROP rozetka_id');
        $this->addSql('ALTER TABLE rozetka_product ADD product_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE rozetka_product ADD CONSTRAINT FK_7DDE23174584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7DDE23174584665A ON rozetka_product (product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product ADD rozetka_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12391873 FOREIGN KEY (rozetka_id) REFERENCES rozetka_product (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD12391873 ON product (rozetka_id)');
        $this->addSql('ALTER TABLE rozetka_product DROP FOREIGN KEY FK_7DDE23174584665A');
        $this->addSql('DROP INDEX UNIQ_7DDE23174584665A ON rozetka_product');
        $this->addSql('ALTER TABLE rozetka_product DROP product_id');
    }
}
