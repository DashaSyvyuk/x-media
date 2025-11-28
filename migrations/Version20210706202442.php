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
        $this->addSql(
            <<<SQL
            CREATE TABLE category (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                status VARCHAR(255) NOT NULL,
                position INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX IDX_64C19C12B36786B (title),
                INDEX IDX_64C19C17B00651C (status),
                INDEX IDX_64C19C1462CE4F5 (position),
                INDEX IDX_64C19C18B8E8428 (created_at),
                INDEX IDX_64C19C143625D9F (updated_at),
                PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL
        );
        $this->addSql(
            <<<SQL
            CREATE TABLE filter_parameter (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                title VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX IDX_FC7C5DFD2B36786B (title),
                PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL
        );
        $this->addSql(
            <<<SQL
            CREATE TABLE filter_parameter_value (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                filter_parameter_id INT UNSIGNED DEFAULT NULL,
                product_id INT UNSIGNED DEFAULT NULL,
                value VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX IDX_63F720858DFB3B41 (filter_parameter_id),
                INDEX IDX_63F720854584665A (product_id),
                INDEX IDX_63F720851D775834 (value),
                PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL
        );
        $this->addSql(
            <<<SQL
            CREATE TABLE product (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                category_id INT UNSIGNED DEFAULT NULL,
                status VARCHAR(255) NOT NULL,
                title VARCHAR(255) NOT NULL,
                description LONGTEXT NOT NULL,
                price INT NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX IDX_D34A04AD12469DE2 (category_id),
                INDEX IDX_D34A04AD7B00651C (status),
                INDEX IDX_D34A04AD2B36786B (title),
                INDEX IDX_D34A04AD8B8E8428 (created_at),
                INDEX IDX_D34A04AD43625D9F (updated_at),
                PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL
        );
        $this->addSql(
            <<<SQL
            CREATE TABLE product_image (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                product_id INT UNSIGNED NOT NULL,
                image_url LONGTEXT NOT NULL,
                position INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX IDX_64617F034584665A (product_id),
                INDEX IDX_64617F034584665A462CE4F5 (product_id, position), 
                PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL
        );
        $this->addSql(
            <<<SQL
            CREATE TABLE slider (
                id INT AUTO_INCREMENT NOT NULL,
                title VARCHAR(255) DEFAULT NULL,
                url LONGTEXT NOT NULL,
                image_url LONGTEXT NOT NULL,
                priority INT DEFAULT NULL,
                PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL
        );
        $this->addSql(
            <<<SQL
            ALTER TABLE filter_parameter_value
                ADD CONSTRAINT FK_63F720858DFB3B41 
                    FOREIGN KEY (filter_parameter_id) REFERENCES filter_parameter (id) ON DELETE SET NULL
            SQL
        );
        $this->addSql(
            <<<SQL
            ALTER TABLE filter_parameter_value 
                ADD CONSTRAINT FK_63F720854584665A 
                    FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL
            SQL
        );
        $this->addSql(
            <<<SQL
            ALTER TABLE product 
                ADD CONSTRAINT FK_D34A04AD12469DE2 
                    FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL
            SQL
        );
        $this->addSql(
            <<<SQL
            ALTER TABLE product_image 
                ADD CONSTRAINT FK_64617F034584665A 
                    FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE filter_parameter_value DROP FOREIGN KEY FK_63F720858DFB3B41');
        $this->addSql('ALTER TABLE filter_parameter_value DROP FOREIGN KEY FK_63F720854584665A');
        $this->addSql('ALTER TABLE product_image DROP FOREIGN KEY FK_64617F034584665A');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE filter_parameter');
        $this->addSql('DROP TABLE filter_parameter_value');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE product_image');
        $this->addSql('DROP TABLE slider');
    }
}
