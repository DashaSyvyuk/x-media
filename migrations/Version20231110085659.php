<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231110085659 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_E52FFDEE444F97DD ON orders');
        $this->addSql('DROP INDEX IDX_E52FFDEE551F0F81 ON orders');
        $this->addSql('DROP INDEX IDX_E52FFDEE7B00651C ON orders');
        $this->addSql('DROP INDEX IDX_E52FFDEEE7769B0F ON orders');
        $this->addSql('DROP INDEX IDX_E52FFDEEE7927C74 ON orders');
        $this->addSql('CREATE INDEX IDX_E52FFDEE444F97DD ON orders (phone)');
        $this->addSql('CREATE INDEX IDX_E52FFDEE551F0F81 ON orders (order_number)');
        $this->addSql('CREATE INDEX IDX_E52FFDEE7B00651C ON orders (status)');
        $this->addSql('CREATE INDEX IDX_E52FFDEEE7769B0F ON orders (surname)');
        $this->addSql('CREATE INDEX IDX_E52FFDEEE7927C74 ON orders (email)');
        $this->addSql('ALTER TABLE orders RENAME INDEX fk_e52ffdeeb5e4a15f TO IDX_E52FFDEEB5E4A15F');
        $this->addSql('ALTER TABLE orders RENAME INDEX fk_e52ffdeef216ee8e TO IDX_E52FFDEEF216EE8E');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_E52FFDEE551F0F81 ON orders');
        $this->addSql('DROP INDEX IDX_E52FFDEEE7769B0F ON orders');
        $this->addSql('DROP INDEX IDX_E52FFDEE444F97DD ON orders');
        $this->addSql('DROP INDEX IDX_E52FFDEEE7927C74 ON orders');
        $this->addSql('DROP INDEX IDX_E52FFDEE7B00651C ON orders');
        $this->addSql('CREATE INDEX IDX_E52FFDEE551F0F81 ON orders (order_number(191))');
        $this->addSql('CREATE INDEX IDX_E52FFDEEE7769B0F ON orders (surname(191))');
        $this->addSql('CREATE INDEX IDX_E52FFDEE444F97DD ON orders (phone(191))');
        $this->addSql('CREATE INDEX IDX_E52FFDEEE7927C74 ON orders (email(191))');
        $this->addSql('CREATE INDEX IDX_E52FFDEE7B00651C ON orders (status(191))');
        $this->addSql('ALTER TABLE orders RENAME INDEX idx_e52ffdeeb5e4a15f TO FK_E52FFDEEB5E4A15F');
        $this->addSql('ALTER TABLE orders RENAME INDEX idx_e52ffdeef216ee8e TO FK_E52FFDEEF216EE8E');
    }
}
