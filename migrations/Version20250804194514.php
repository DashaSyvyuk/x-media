<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250804194514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rozetka_product DROP INDEX UNIQ_7DDE2317A0EAF80A, ADD INDEX IDX_7DDE2317A0EAF80A (rozetka_product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rozetka_product DROP INDEX IDX_7DDE2317A0EAF80A, ADD UNIQUE INDEX UNIQ_7DDE2317A0EAF80A (rozetka_product_id)');
    }
}
