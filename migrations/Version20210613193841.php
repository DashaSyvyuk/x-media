<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210613193841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_filter_parameter_value (product_id INT UNSIGNED NOT NULL, filter_parameter_value_id INT UNSIGNED NOT NULL, INDEX IDX_213117634584665A (product_id), INDEX IDX_2131176337E25F25 (filter_parameter_value_id), PRIMARY KEY(product_id, filter_parameter_value_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product_filter_parameter_value ADD CONSTRAINT FK_213117634584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_filter_parameter_value ADD CONSTRAINT FK_2131176337E25F25 FOREIGN KEY (filter_parameter_value_id) REFERENCES filter_parameter_value (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE product_filter_parameter_value');
    }
}
