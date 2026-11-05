<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261106120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Asana-style planning boards and plan card status/position fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE admin_plan_boards (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(120) NOT NULL,
            color VARCHAR(20) NOT NULL DEFAULT \'teal\',
            position INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_ADMIN_PLAN_BOARD_POS (position),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql("INSERT INTO admin_plan_boards (title, color, position, created_at, updated_at)
            VALUES ('Загальне', 'teal', 0, NOW(), NOW())");

        $this->addSql('ALTER TABLE admin_plans
            ADD board_id INT DEFAULT NULL,
            ADD status VARCHAR(20) NOT NULL DEFAULT \'new\',
            ADD position INT UNSIGNED NOT NULL DEFAULT 0,
            CHANGE scheduled_date scheduled_date DATE DEFAULT NULL,
            CHANGE assignee_id assignee_id INT DEFAULT NULL');

        $this->addSql('UPDATE admin_plans SET status = \'completed\' WHERE completed_at IS NOT NULL');
        $this->addSql('UPDATE admin_plans SET status = \'new\' WHERE completed_at IS NULL');
        $this->addSql('UPDATE admin_plans p
            INNER JOIN admin_plan_boards b ON b.title = \'Загальне\'
            SET p.board_id = b.id
            WHERE p.board_id IS NULL');
        $this->addSql('UPDATE admin_plans SET position = id WHERE position = 0');

        $this->addSql('ALTER TABLE admin_plans MODIFY board_id INT NOT NULL');
        $this->addSql('ALTER TABLE admin_plans ADD CONSTRAINT FK_ADMIN_PLAN_BOARD FOREIGN KEY (board_id) REFERENCES admin_plan_boards (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_ADMIN_PLAN_BOARD ON admin_plans (board_id)');
        $this->addSql('CREATE INDEX IDX_ADMIN_PLAN_STATUS ON admin_plans (status)');
        $this->addSql('CREATE INDEX IDX_ADMIN_PLAN_BOARD_STATUS_POS ON admin_plans (board_id, status, position)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE admin_plans DROP FOREIGN KEY FK_ADMIN_PLAN_BOARD');
        $this->addSql('DROP INDEX IDX_ADMIN_PLAN_BOARD ON admin_plans');
        $this->addSql('DROP INDEX IDX_ADMIN_PLAN_STATUS ON admin_plans');
        $this->addSql('DROP INDEX IDX_ADMIN_PLAN_BOARD_STATUS_POS ON admin_plans');
        $this->addSql('UPDATE admin_plans SET scheduled_date = CURDATE() WHERE scheduled_date IS NULL');
        $this->addSql('ALTER TABLE admin_plans
            DROP board_id,
            DROP status,
            DROP position,
            CHANGE scheduled_date scheduled_date DATE NOT NULL');
        // assignee_id stays nullable on down to avoid data loss if nulls exist
        $this->addSql('DROP TABLE admin_plan_boards');
    }
}
