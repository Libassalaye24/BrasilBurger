<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220630150857 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commandes_burger (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE burger ADD commandes_burger_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE burger ADD CONSTRAINT FK_EFE35A0D4E16C07B FOREIGN KEY (commandes_burger_id) REFERENCES commandes_burger (id)');
        $this->addSql('CREATE INDEX IDX_EFE35A0D4E16C07B ON burger (commandes_burger_id)');
        $this->addSql('ALTER TABLE commande ADD commandes_burger_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D4E16C07B FOREIGN KEY (commandes_burger_id) REFERENCES commandes_burger (id)');
        $this->addSql('CREATE INDEX IDX_6EEAA67D4E16C07B ON commande (commandes_burger_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE burger DROP FOREIGN KEY FK_EFE35A0D4E16C07B');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D4E16C07B');
        $this->addSql('DROP TABLE commandes_burger');
        $this->addSql('DROP INDEX IDX_EFE35A0D4E16C07B ON burger');
        $this->addSql('ALTER TABLE burger DROP commandes_burger_id');
        $this->addSql('DROP INDEX IDX_6EEAA67D4E16C07B ON commande');
        $this->addSql('ALTER TABLE commande DROP commandes_burger_id');
    }
}
