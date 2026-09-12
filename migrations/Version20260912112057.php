<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260912112057 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE jeu_comment (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, date_modification DATETIME DEFAULT NULL, is_deleted TINYINT NOT NULL, jeu_id INT NOT NULL, auteur_id INT NOT NULL, INDEX IDX_D1185A868C9E392E (jeu_id), INDEX IDX_D1185A8660BB6FE6 (auteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE jeu_comment ADD CONSTRAINT FK_D1185A868C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id)');
        $this->addSql('ALTER TABLE jeu_comment ADD CONSTRAINT FK_D1185A8660BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE jeu_comment DROP FOREIGN KEY FK_D1185A868C9E392E');
        $this->addSql('ALTER TABLE jeu_comment DROP FOREIGN KEY FK_D1185A8660BB6FE6');
        $this->addSql('DROP TABLE jeu_comment');
    }
}
