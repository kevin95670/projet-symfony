<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261112150024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE signalement_jeu (id INT AUTO_INCREMENT NOT NULL, motif VARCHAR(50) NOT NULL, commentaire LONGTEXT DEFAULT NULL, date_creation DATETIME NOT NULL, traite TINYINT DEFAULT 0 NOT NULL, jeu_id INT NOT NULL, auteur_id INT NOT NULL, INDEX IDX_5EE87CF88C9E392E (jeu_id), INDEX IDX_5EE87CF860BB6FE6 (auteur_id), UNIQUE INDEX UNIQ_SIGNALEMENT_JEU_AUTEUR (jeu_id, auteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE signalement_jeu ADD CONSTRAINT FK_5EE87CF88C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE signalement_jeu ADD CONSTRAINT FK_5EE87CF860BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE jeu ADD masque TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE signalement_jeu DROP FOREIGN KEY FK_5EE87CF88C9E392E');
        $this->addSql('ALTER TABLE signalement_jeu DROP FOREIGN KEY FK_5EE87CF860BB6FE6');
        $this->addSql('DROP TABLE signalement_jeu');
        $this->addSql('ALTER TABLE jeu DROP masque');
    }
}
