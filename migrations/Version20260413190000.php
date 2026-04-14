<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert quiz active boolean to status integer with review workflow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quiz MODIFY active SMALLINT NOT NULL DEFAULT 2');
        $this->addSql('UPDATE quiz SET active = CASE WHEN active = 1 THEN 1 ELSE 0 END');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE quiz SET active = CASE WHEN active = 1 THEN 1 ELSE 0 END');
        $this->addSql('ALTER TABLE quiz MODIFY active TINYINT(1) NOT NULL DEFAULT 1');
    }
}
