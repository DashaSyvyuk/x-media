<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210706202442 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE filter_parameter_value ADD product_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE filter_parameter_value ADD CONSTRAINT FK_63F720854584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_63F720854584665A ON filter_parameter_value (product_id)');
        $this->addSql('DROP TABLE product_filter_parameter_value');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE filter_parameter_value DROP FOREIGN KEY FK_63F720854584665A');
        $this->addSql('DROP INDEX IDX_63F720854584665A ON filter_parameter_value');
        $this->addSql('ALTER TABLE filter_parameter_value DROP product_id');
    }
}
