<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328144403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE registrations ADD CONSTRAINT FK_53DE51E771F7E88B FOREIGN KEY (event_id) REFERENCES event (id_event)');
        $this->addSql('CREATE INDEX IDX_53DE51E771F7E88B ON registrations (event_id)');
        $this->addSql('ALTER TABLE review CHANGE content content LONGTEXT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE review RENAME INDEX fk_usr_idx TO IDX_794381C689C6A1D6');
        $this->addSql('ALTER TABLE review_reply CHANGE content content LONGTEXT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE review_reply RENAME INDEX fk_review_idx TO IDX_EE26EAFB5F74342B');
        $this->addSql('ALTER TABLE review_reply RENAME INDEX fk_therapist_idx TO IDX_EE26EAFB8C08DF08');
        $this->addSql('ALTER TABLE therapists CHANGE description description LONGTEXT DEFAULT NULL, CHANGE consultation_type consultation_type VARCHAR(20) DEFAULT NULL, CHANGE status status VARCHAR(10) DEFAULT \'ACTIVE\' NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE therapists RENAME INDEX email TO UNIQ_B0D3CF31E7927C74');
        $this->addSql('ALTER TABLE users CHANGE role role VARCHAR(20) DEFAULT \'patient\' NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE users RENAME INDEX username TO UNIQ_1483A5E9A9D1C132');
        $this->addSql('ALTER TABLE users RENAME INDEX email TO UNIQ_1483A5E9E7927C74');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE registrations DROP FOREIGN KEY FK_53DE51E771F7E88B');
        $this->addSql('DROP INDEX IDX_53DE51E771F7E88B ON registrations');
        $this->addSql('ALTER TABLE review CHANGE content content TEXT NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE review RENAME INDEX idx_794381c689c6a1d6 TO fk_usr_idx');
        $this->addSql('ALTER TABLE review_reply CHANGE content content TEXT NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE review_reply RENAME INDEX idx_ee26eafb8c08df08 TO fk_therapist_idx');
        $this->addSql('ALTER TABLE review_reply RENAME INDEX idx_ee26eafb5f74342b TO fk_review_idx');
        $this->addSql('ALTER TABLE therapists CHANGE description description TEXT DEFAULT NULL, CHANGE consultation_type consultation_type ENUM(\'ONLINE\', \'IN_PERSON\', \'BOTH\') DEFAULT NULL, CHANGE status status ENUM(\'ACTIVE\', \'INACTIVE\') DEFAULT \'ACTIVE\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE therapists RENAME INDEX uniq_b0d3cf31e7927c74 TO email');
        $this->addSql('ALTER TABLE users CHANGE role role VARCHAR(20) DEFAULT \'patient\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE users RENAME INDEX uniq_1483a5e9e7927c74 TO email');
        $this->addSql('ALTER TABLE users RENAME INDEX uniq_1483a5e9a9d1c132 TO username');
    }
}
