<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260507000435 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE notification');
        $this->addSql('ALTER TABLE blog DROP audio_path');
        $this->addSql('ALTER TABLE blog RENAME INDEX fk_blog_category_idx TO IDX_C015514312469DE2');
        $this->addSql('ALTER TABLE blog_favorite DROP FOREIGN KEY `fk_user`');
        $this->addSql('DROP INDEX user_id ON blog_favorite');
        $this->addSql('ALTER TABLE blog_favorite DROP created_at, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE blog_favorite ADD CONSTRAINT FK_1C98AAFEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE blog_favorite RENAME INDEX fk_blog TO IDX_1C98AAFEDAE07E97');
        $this->addSql('ALTER TABLE blog_like DROP FOREIGN KEY `FK_4CB3CC23DAE07E97`');
        $this->addSql('ALTER TABLE blog_like ADD CONSTRAINT FK_4CB3CC23DAE07E97 FOREIGN KEY (blog_id) REFERENCES blog (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande CHANGE total_amount total_amount NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY `FK_9474526CDAE07E97`');
        $this->addSql('ALTER TABLE comment DROP sentiment, CHANGE content content LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CDAE07E97 FOREIGN KEY (blog_id) REFERENCES blog (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment_like DROP FOREIGN KEY `FK_8A55E25FF8697D13`');
        $this->addSql('ALTER TABLE comment_like ADD CONSTRAINT FK_8A55E25FF8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conversations CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE conversations ADD CONSTRAINT FK_C2521BF1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conversations ADD CONSTRAINT FK_C2521BF143E8B094 FOREIGN KEY (therapist_id) REFERENCES therapists (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conversations RENAME INDEX user_id TO IDX_C2521BF1A76ED395');
        $this->addSql('ALTER TABLE conversations RENAME INDEX therapist_id TO IDX_C2521BF143E8B094');
        $this->addSql('ALTER TABLE event DROP short_url');
        $this->addSql('ALTER TABLE event_subscriptions CHANGE subscribed_at subscribed_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE event_subscriptions RENAME INDEX idx_sub_user TO IDX_202E3AADA76ED395');
        $this->addSql('ALTER TABLE event_subscriptions RENAME INDEX idx_sub_event TO IDX_202E3AAD71F7E88B');
        $this->addSql('ALTER TABLE messages CHANGE sender_type sender_type ENUM(\'user\', \'therapist\'), CHANGE content content LONGTEXT NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE sensitivity_level sensitivity_level VARCHAR(20) DEFAULT \'low\' NOT NULL, CHANGE ai_analysis ai_analysis LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E969AC0396 FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE messages RENAME INDEX conversation_id TO IDX_DB021E969AC0396');
        $this->addSql('ALTER TABLE notifications CHANGE created_at created_at DATETIME NOT NULL, CHANGE is_read is_read TINYINT NOT NULL');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D371F7E88B FOREIGN KEY (event_id) REFERENCES event (id_event) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_6000B0D371F7E88B ON notifications (event_id)');
        $this->addSql('ALTER TABLE notifications RENAME INDEX idx_notif_user TO IDX_6000B0D3A76ED395');
        $this->addSql('ALTER TABLE products CHANGE category category ENUM(\'Authorized Vitamins\', \'Psychology Books\', \'Relaxing Products\', \'Therapeutic Games & Activities\')');
        $this->addSql('ALTER TABLE quiz CHANGE author_id author_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, message TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, is_read TINYINT DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, user_id INT DEFAULT NULL, blog_id INT DEFAULT NULL, INDEX fk_notification_user (user_id), INDEX fk_notification_blog (blog_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE blog ADD audio_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE blog RENAME INDEX idx_c015514312469de2 TO FK_BLOG_CATEGORY_idx');
        $this->addSql('ALTER TABLE blog_favorite DROP FOREIGN KEY FK_1C98AAFEA76ED395');
        $this->addSql('ALTER TABLE blog_favorite ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE blog_favorite ADD CONSTRAINT `fk_user` FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX user_id ON blog_favorite (user_id, blog_id)');
        $this->addSql('ALTER TABLE blog_favorite RENAME INDEX idx_1c98aafedae07e97 TO fk_blog');
        $this->addSql('ALTER TABLE blog_like DROP FOREIGN KEY FK_4CB3CC23DAE07E97');
        $this->addSql('ALTER TABLE blog_like ADD CONSTRAINT `FK_4CB3CC23DAE07E97` FOREIGN KEY (blog_id) REFERENCES blog (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE commande CHANGE total_amount total_amount DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CDAE07E97');
        $this->addSql('ALTER TABLE comment ADD sentiment VARCHAR(12) DEFAULT NULL, CHANGE content content TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT `FK_9474526CDAE07E97` FOREIGN KEY (blog_id) REFERENCES blog (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE comment_like DROP FOREIGN KEY FK_8A55E25FF8697D13');
        $this->addSql('ALTER TABLE comment_like ADD CONSTRAINT `FK_8A55E25FF8697D13` FOREIGN KEY (comment_id) REFERENCES comment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE conversations DROP FOREIGN KEY FK_C2521BF1A76ED395');
        $this->addSql('ALTER TABLE conversations DROP FOREIGN KEY FK_C2521BF143E8B094');
        $this->addSql('ALTER TABLE conversations CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE conversations RENAME INDEX idx_c2521bf1a76ed395 TO user_id');
        $this->addSql('ALTER TABLE conversations RENAME INDEX idx_c2521bf143e8b094 TO therapist_id');
        $this->addSql('ALTER TABLE event ADD short_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE event_subscriptions CHANGE subscribed_at subscribed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE event_subscriptions RENAME INDEX idx_202e3aada76ed395 TO IDX_SUB_USER');
        $this->addSql('ALTER TABLE event_subscriptions RENAME INDEX idx_202e3aad71f7e88b TO IDX_SUB_EVENT');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E969AC0396');
        $this->addSql('ALTER TABLE messages CHANGE sender_type sender_type ENUM(\'user\', \'therapist\') NOT NULL, CHANGE content content TEXT NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE sensitivity_level sensitivity_level VARCHAR(20) DEFAULT \'low\', CHANGE ai_analysis ai_analysis TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE messages RENAME INDEX idx_db021e969ac0396 TO conversation_id');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D371F7E88B');
        $this->addSql('DROP INDEX IDX_6000B0D371F7E88B ON notifications');
        $this->addSql('ALTER TABLE notifications CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE is_read is_read TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE notifications RENAME INDEX idx_6000b0d3a76ed395 TO IDX_NOTIF_USER');
        $this->addSql('ALTER TABLE products CHANGE category category ENUM(\'Authorized Vitamins\', \'Psychology Books\', \'Relaxing Products\', \'Therapeutic Games & Activities\') DEFAULT NULL');
        $this->addSql('ALTER TABLE quiz CHANGE author_id author_id INT DEFAULT NULL');
    }
}
