<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260810112902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(80) NOT NULL, slug VARCHAR(30) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE categorie_jeu (categorie_id INT NOT NULL, jeu_id INT NOT NULL, INDEX IDX_AC534CB4BCF5E72D (categorie_id), INDEX IDX_AC534CB48C9E392E (jeu_id), PRIMARY KEY (categorie_id, jeu_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, date_commande DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, reference VARCHAR(255) NOT NULL, user_id INT NOT NULL, INDEX IDX_6EEAA67DA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commande_lignes (id INT AUTO_INCREMENT NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, quantite INT NOT NULL, jeu_id INT NOT NULL, commande_id INT NOT NULL, INDEX IDX_6DBC169F8C9E392E (jeu_id), INDEX IDX_6DBC169F82EA2E54 (commande_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE jeu (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, prix NUMERIC(10, 2) NOT NULL, date_creation DATE NOT NULL, plateforme VARCHAR(255) NOT NULL, pegi INT NOT NULL, etat VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, vendeur_id INT NOT NULL, INDEX IDX_82E48DB5858C065E (vendeur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE panier (id INT AUTO_INCREMENT NOT NULL, date_creation DATETIME NOT NULL, mise_a_jour DATETIME NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_24CC0DF2A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE panier_item (id INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, jeu_id INT NOT NULL, panier_id INT NOT NULL, INDEX IDX_EBFD00678C9E392E (jeu_id), INDEX IDX_EBFD0067F77D927C (panier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, prenom VARCHAR(50) NOT NULL, nom VARCHAR(70) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, pseudo VARCHAR(30) NOT NULL, date_naissance DATE NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), UNIQUE INDEX UNIQ_IDENTIFIER_PSEUDO (pseudo), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE categorie_jeu ADD CONSTRAINT FK_AC534CB4BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE categorie_jeu ADD CONSTRAINT FK_AC534CB48C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE commande_lignes ADD CONSTRAINT FK_6DBC169F8C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id)');
        $this->addSql('ALTER TABLE commande_lignes ADD CONSTRAINT FK_6DBC169F82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('ALTER TABLE jeu ADD CONSTRAINT FK_82E48DB5858C065E FOREIGN KEY (vendeur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE panier_item ADD CONSTRAINT FK_EBFD00678C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id)');
        $this->addSql('ALTER TABLE panier_item ADD CONSTRAINT FK_EBFD0067F77D927C FOREIGN KEY (panier_id) REFERENCES panier (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categorie_jeu DROP FOREIGN KEY FK_AC534CB4BCF5E72D');
        $this->addSql('ALTER TABLE categorie_jeu DROP FOREIGN KEY FK_AC534CB48C9E392E');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('ALTER TABLE commande_lignes DROP FOREIGN KEY FK_6DBC169F8C9E392E');
        $this->addSql('ALTER TABLE commande_lignes DROP FOREIGN KEY FK_6DBC169F82EA2E54');
        $this->addSql('ALTER TABLE jeu DROP FOREIGN KEY FK_82E48DB5858C065E');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF2A76ED395');
        $this->addSql('ALTER TABLE panier_item DROP FOREIGN KEY FK_EBFD00678C9E392E');
        $this->addSql('ALTER TABLE panier_item DROP FOREIGN KEY FK_EBFD0067F77D927C');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE categorie_jeu');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE commande_lignes');
        $this->addSql('DROP TABLE jeu');
        $this->addSql('DROP TABLE panier');
        $this->addSql('DROP TABLE panier_item');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
