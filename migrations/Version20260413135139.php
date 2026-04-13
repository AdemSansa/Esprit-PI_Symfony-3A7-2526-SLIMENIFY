<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260413135139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
     
        $this->addSql('ALTER TABLE quiz ADD author_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE quiz ADD CONSTRAINT FK_A412FA92F675F31B FOREIGN KEY (author_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_A412FA92F675F31B ON quiz (author_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE blog (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, content TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, photo VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, therapist_id INT NOT NULL, INDEX therapist_id (therapist_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE blog_like (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, therapist_id INT DEFAULT NULL, blog_id INT DEFAULT NULL, INDEX blog_id (blog_id), UNIQUE INDEX therapist_id (therapist_id, blog_id), UNIQUE INDEX user_id (user_id, blog_id), INDEX IDX_4CB3CC23A76ED395 (user_id), INDEX IDX_4CB3CC2343E8B094 (therapist_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, total_amount DOUBLE PRECISION NOT NULL, status VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'en_attente\' NOT NULL COLLATE `utf8mb4_unicode_ci`, shipping_address VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, contact_phone VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, payment_method VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, items_details JSON NOT NULL, INDEX FK_COMMANDE_USER (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, content TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, user_id INT DEFAULT NULL, therapist_id INT DEFAULT NULL, blog_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, INDEX user_id (user_id), INDEX therapist_id (therapist_id), INDEX blog_id (blog_id), INDEX parent_id (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE comment_like (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, therapist_id INT DEFAULT NULL, comment_id INT DEFAULT NULL, UNIQUE INDEX user_id (user_id, comment_id), UNIQUE INDEX therapist_id (therapist_id, comment_id), INDEX comment_id (comment_id), INDEX IDX_8A55E25FA76ED395 (user_id), INDEX IDX_8A55E25F43E8B094 (therapist_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE blog ADD CONSTRAINT `blog_ibfk_1` FOREIGN KEY (therapist_id) REFERENCES therapists (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE blog_like ADD CONSTRAINT `blog_like_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE blog_like ADD CONSTRAINT `blog_like_ibfk_2` FOREIGN KEY (therapist_id) REFERENCES therapists (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE blog_like ADD CONSTRAINT `blog_like_ibfk_3` FOREIGN KEY (blog_id) REFERENCES blog (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT `FK_COMMANDE_USER` FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT `comment_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT `comment_ibfk_2` FOREIGN KEY (therapist_id) REFERENCES therapists (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT `comment_ibfk_3` FOREIGN KEY (blog_id) REFERENCES blog (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT `comment_ibfk_4` FOREIGN KEY (parent_id) REFERENCES comment (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment_like ADD CONSTRAINT `comment_like_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment_like ADD CONSTRAINT `comment_like_ibfk_2` FOREIGN KEY (therapist_id) REFERENCES therapists (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment_like ADD CONSTRAINT `comment_like_ibfk_3` FOREIGN KEY (comment_id) REFERENCES comment (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reply_review DROP FOREIGN KEY FK_703A27D65F74342B');
        $this->addSql('ALTER TABLE reply_review DROP FOREIGN KEY FK_703A27D68C08DF08');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6E173B1B8');
        $this->addSql('DROP TABLE reply_review');
        $this->addSql('DROP TABLE review');
        $this->addSql('ALTER TABLE products CHANGE category category ENUM(\'Authorized Vitamins\', \'Psychology Books\', \'Relaxing Products\', \'Therapeutic Games & Activities\') DEFAULT NULL');
        $this->addSql('ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA92F675F31B');
        $this->addSql('DROP INDEX IDX_A412FA92F675F31B ON quiz');
        $this->addSql('ALTER TABLE quiz DROP author_id');
    }
}
