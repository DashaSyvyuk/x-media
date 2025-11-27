<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250329181739 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rozetka_product ADD active_for_p TINYINT(1) NOT NULL AFTER active_for_a, CHANGE active active_for_a TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE rozetka_product ADD crossed_out_price INT DEFAULT NULL AFTER price');
        $this->addSql('ALTER TABLE rozetka_product ADD rozetka_product_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE rozetka_product ADD CONSTRAINT FK_7DDE2317A0EAF80A FOREIGN KEY (rozetka_product_id) REFERENCES rozetka_product (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7DDE2317A0EAF80A ON rozetka_product (rozetka_product_id)');
        $this->addSql('ALTER TABLE rozetka_product DROP INDEX UNIQ_7DDE2317A0EAF80A, ADD INDEX IDX_7DDE2317A0EAF80A (rozetka_product_id)');
        $this->addSql('ALTER TABLE rozetka_product ADD promo_price INT DEFAULT NULL, ADD promo_price_active TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rozetka_product CHANGE active_for_a active TINYINT(1) NOT NULL, DROP active_for_a, DROP active_for_p');
        $this->addSql('ALTER TABLE rozetka_product DROP crossed_out_price');
        $this->addSql('ALTER TABLE rozetka_product DROP FOREIGN KEY FK_7DDE2317A0EAF80A');
        $this->addSql('DROP INDEX UNIQ_7DDE2317A0EAF80A ON rozetka_product');
        $this->addSql('ALTER TABLE rozetka_product DROP rozetka_product_id');
        $this->addSql('ALTER TABLE rozetka_product DROP INDEX IDX_7DDE2317A0EAF80A, ADD UNIQUE INDEX UNIQ_7DDE2317A0EAF80A (rozetka_product_id)');
        $this->addSql('ALTER TABLE rozetka_product DROP promo_price, DROP promo_price_active');
    }
}
