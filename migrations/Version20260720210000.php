<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Admin web-push subscriptions and per-user notification preferences';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE admin_user ADD notify_local_orders TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE admin_user ADD notify_rozetka_orders TINYINT(1) DEFAULT 1 NOT NULL');

        $this->addSql('CREATE TABLE admin_push_subscriptions (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            endpoint TEXT NOT NULL,
            endpoint_hash VARCHAR(64) NOT NULL,
            p256dh VARCHAR(255) NOT NULL,
            auth VARCHAR(255) NOT NULL,
            user_agent VARCHAR(512) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_ADMIN_PUSH_ENDPOINT_HASH (endpoint_hash),
            INDEX IDX_ADMIN_PUSH_USER (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE admin_push_subscriptions ADD CONSTRAINT FK_ADMIN_PUSH_USER FOREIGN KEY (user_id) REFERENCES admin_user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE admin_push_subscriptions DROP FOREIGN KEY FK_ADMIN_PUSH_USER');
        $this->addSql('DROP TABLE admin_push_subscriptions');
        $this->addSql('ALTER TABLE admin_user DROP notify_local_orders');
        $this->addSql('ALTER TABLE admin_user DROP notify_rozetka_orders');
    }
}
