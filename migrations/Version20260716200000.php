<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add admin planning tasks and active flag for admin users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE admin_user ADD active TINYINT(1) DEFAULT 1 NOT NULL');

        $this->addSql('CREATE TABLE admin_plans (
            id INT AUTO_INCREMENT NOT NULL,
            assignee_id INT NOT NULL,
            created_by_id INT DEFAULT NULL,
            scheduled_date DATE NOT NULL,
            title VARCHAR(255) NOT NULL,
            body LONGTEXT DEFAULT NULL,
            completed_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_ADMIN_PLAN_ASSIGNEE (assignee_id),
            INDEX IDX_ADMIN_PLAN_CREATED_BY (created_by_id),
            INDEX IDX_ADMIN_PLAN_DATE (scheduled_date),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE admin_plans ADD CONSTRAINT FK_ADMIN_PLAN_ASSIGNEE FOREIGN KEY (assignee_id) REFERENCES admin_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE admin_plans ADD CONSTRAINT FK_ADMIN_PLAN_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES admin_user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE admin_plans DROP FOREIGN KEY FK_ADMIN_PLAN_ASSIGNEE');
        $this->addSql('ALTER TABLE admin_plans DROP FOREIGN KEY FK_ADMIN_PLAN_CREATED_BY');
        $this->addSql('DROP TABLE admin_plans');
        $this->addSql('ALTER TABLE admin_user DROP active');
    }
}
