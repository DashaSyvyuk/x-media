<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230626113746 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orders DROP COLUMN paytype, DROP COLUMN deltype');
        $this->addSql('ALTER TABLE orders ADD paytype_id INT UNSIGNED DEFAULT NULL, ADD deltype_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEB5E4A15F FOREIGN KEY (paytype_id) REFERENCES payment_type (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEF216EE8E FOREIGN KEY (deltype_id) REFERENCES delivery_type (id) ON DELETE SET NULL');

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEB5E4A15F');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEF216EE8E');
        $this->addSql('ALTER TABLE orders CHANGE paytype_id paytype_id INT UNSIGNED NOT NULL, CHANGE deltype_id deltype_id INT UNSIGNED NOT NULL');
    }
}
