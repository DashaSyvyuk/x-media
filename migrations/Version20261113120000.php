<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261113120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Per-board planning columns instead of fixed plan statuses';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE admin_plan_columns (
            id INT AUTO_INCREMENT NOT NULL,
            board_id INT NOT NULL,
            title VARCHAR(120) NOT NULL,
            color VARCHAR(20) NOT NULL,
            position INT UNSIGNED NOT NULL DEFAULT 0,
            is_done TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_ADMIN_PLAN_COLUMN_BOARD (board_id),
            INDEX IDX_ADMIN_PLAN_COLUMN_BOARD_POS (board_id, position),
            PRIMARY KEY(id),
            CONSTRAINT FK_ADMIN_PLAN_COLUMN_BOARD FOREIGN KEY (board_id)
                REFERENCES admin_plan_boards (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql("INSERT INTO admin_plan_columns (board_id, title, color, position, is_done, created_at, updated_at)
            SELECT b.id, 'Нове', 'blue', 0, 0, NOW(), NOW() FROM admin_plan_boards b");
        $this->addSql("INSERT INTO admin_plan_columns (board_id, title, color, position, is_done, created_at, updated_at)
            SELECT b.id, 'В роботі', 'amber', 1, 0, NOW(), NOW() FROM admin_plan_boards b");
        $this->addSql("INSERT INTO admin_plan_columns (board_id, title, color, position, is_done, created_at, updated_at)
            SELECT b.id, 'Завершено', 'lime', 2, 1, NOW(), NOW() FROM admin_plan_boards b");

        $this->addSql('ALTER TABLE admin_plans ADD column_id INT DEFAULT NULL');

        $this->addSql("UPDATE admin_plans p
            INNER JOIN admin_plan_columns c ON c.board_id = p.board_id AND c.title = 'Нове'
            SET p.column_id = c.id
            WHERE p.status = 'new' OR p.status IS NULL OR p.status = ''");
        $this->addSql("UPDATE admin_plans p
            INNER JOIN admin_plan_columns c ON c.board_id = p.board_id AND c.title = 'В роботі'
            SET p.column_id = c.id
            WHERE p.status = 'in_progress'");
        $this->addSql("UPDATE admin_plans p
            INNER JOIN admin_plan_columns c ON c.board_id = p.board_id AND c.title = 'Завершено'
            SET p.column_id = c.id
            WHERE p.status = 'completed'");
        $this->addSql("UPDATE admin_plans p
            INNER JOIN admin_plan_columns c ON c.board_id = p.board_id AND c.title = 'Нове'
            SET p.column_id = c.id
            WHERE p.column_id IS NULL");

        $this->addSql('ALTER TABLE admin_plans MODIFY column_id INT NOT NULL');
        $this->addSql('ALTER TABLE admin_plans ADD CONSTRAINT FK_ADMIN_PLAN_COLUMN FOREIGN KEY (column_id)
            REFERENCES admin_plan_columns (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_ADMIN_PLAN_COLUMN ON admin_plans (column_id)');
        $this->addSql('CREATE INDEX IDX_ADMIN_PLAN_BOARD_COLUMN_POS ON admin_plans (board_id, column_id, position)');

        $this->addSql('ALTER TABLE admin_plans DROP INDEX IDX_ADMIN_PLAN_STATUS');
        $this->addSql('ALTER TABLE admin_plans DROP INDEX IDX_ADMIN_PLAN_BOARD_STATUS_POS');
        $this->addSql('ALTER TABLE admin_plans DROP status');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE admin_plans ADD status VARCHAR(20) NOT NULL DEFAULT \'new\'');
        $this->addSql("UPDATE admin_plans p
            INNER JOIN admin_plan_columns c ON c.id = p.column_id
            SET p.status = CASE
                WHEN c.is_done = 1 THEN 'completed'
                WHEN c.title = 'В роботі' THEN 'in_progress'
                ELSE 'new'
            END");

        $this->addSql('ALTER TABLE admin_plans DROP FOREIGN KEY FK_ADMIN_PLAN_COLUMN');
        $this->addSql('DROP INDEX IDX_ADMIN_PLAN_COLUMN ON admin_plans');
        $this->addSql('DROP INDEX IDX_ADMIN_PLAN_BOARD_COLUMN_POS ON admin_plans');
        $this->addSql('ALTER TABLE admin_plans DROP column_id');
        $this->addSql('CREATE INDEX IDX_ADMIN_PLAN_STATUS ON admin_plans (status)');
        $this->addSql('CREATE INDEX IDX_ADMIN_PLAN_BOARD_STATUS_POS ON admin_plans (board_id, status, position)');
        $this->addSql('DROP TABLE admin_plan_columns');
    }
}
