<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250221112940 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE rozetka_product set price = (CEIL(((SELECT price FROM product WHERE product.id=rozetka_product.product_id LIMIT 1) * 1.0869565217391) / 100) * 100 - 1) WHERE price < 20000;');

        $this->addSql('UPDATE rozetka_product set price = (CEIL(((SELECT price FROM product WHERE product.id=rozetka_product.product_id LIMIT 1) * 1.0869565217391) / 1000) * 1000 - 1) WHERE price >= 20000;');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
