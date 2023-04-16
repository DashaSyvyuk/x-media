<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230416153319 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("INSERT INTO `setting` (`title`, `slug`, `value`, `created_at`, `updated_at`) VALUES ('Назва магазину', 'shop_name', 'x-media', NOW(), NOW())");
        $this->addSql("INSERT INTO `setting` (`title`, `slug`, `value`, `created_at`, `updated_at`) VALUES ('Адрес магазну', 'shop_address', 'м.Київ, вул. Вадима Гетьмана 27, каб. 1133', NOW(), NOW())");
        $this->addSql("INSERT INTO `setting` (`title`, `slug`, `value`, `created_at`, `updated_at`) VALUES ('Адрес пункту самовивозу', 'pick_up_point_address', 'м.Київ, вул. Вадима Гетьмана 27, каб. 1133', NOW(), NOW())");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
