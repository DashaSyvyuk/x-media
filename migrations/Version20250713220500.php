<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250713220500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill product.xkom_url from product.note when contains x-kom';
    }

    public function up(Schema $schema): void
    {
        // MySQL 8 supports REGEXP_SUBSTR. Prefer extracting an URL, fall back to note text.
        $this->addSql(<<<'SQL'
UPDATE product
SET xkom_url = COALESCE(
    NULLIF(REGEXP_SUBSTR(note, 'https?://[^[:space:]]*x-kom\\.pl[^[:space:]]*'), ''),
    NULLIF(REGEXP_SUBSTR(note, 'www\\.x-kom\\.pl[^[:space:]]*'), ''),
    NULLIF(TRIM(note), '')
)
WHERE xkom_url IS NULL
  AND note IS NOT NULL
  AND note LIKE '%x-kom%';
SQL);
    }

    public function down(Schema $schema): void
    {
        // Irreversible data backfill.
    }
}

