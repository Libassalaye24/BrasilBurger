<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220630152453 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE burger DROP FOREIGN KEY FK_EFE35A0D4E16C07B');
        $this->addSql('DROP INDEX IDX_EFE35A0D4E16C07B ON burger');
        $this->addSql('ALTER TABLE burger DROP commandes_burger_id');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D4E16C07B');
        $this->addSql('DROP INDEX IDX_6EEAA67D4E16C07B ON commande');
        $this->addSql('ALTER TABLE commande DROP commandes_burger_id');
        $this->addSql('ALTER TABLE commandes_burger ADD burger_id INT DEFAULT NULL, ADD commande_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commandes_burger ADD CONSTRAINT FK_4EB2B83717CE5090 FOREIGN KEY (burger_id) REFERENCES burger (id)');
        $this->addSql('ALTER TABLE commandes_burger ADD CONSTRAINT FK_4EB2B83782EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('CREATE INDEX IDX_4EB2B83717CE5090 ON commandes_burger (burger_id)');
        $this->addSql('CREATE INDEX IDX_4EB2B83782EA2E54 ON commandes_burger (commande_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE burger ADD commandes_burger_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE burger ADD CONSTRAINT FK_EFE35A0D4E16C07B FOREIGN KEY (commandes_burger_id) REFERENCES commandes_burger (id)');
        $this->addSql('CREATE INDEX IDX_EFE35A0D4E16C07B ON burger (commandes_burger_id)');
        $this->addSql('ALTER TABLE commande ADD commandes_burger_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D4E16C07B FOREIGN KEY (commandes_burger_id) REFERENCES commandes_burger (id)');
        $this->addSql('CREATE INDEX IDX_6EEAA67D4E16C07B ON commande (commandes_burger_id)');
        $this->addSql('ALTER TABLE commandes_burger DROP FOREIGN KEY FK_4EB2B83717CE5090');
        $this->addSql('ALTER TABLE commandes_burger DROP FOREIGN KEY FK_4EB2B83782EA2E54');
        $this->addSql('DROP INDEX IDX_4EB2B83717CE5090 ON commandes_burger');
        $this->addSql('DROP INDEX IDX_4EB2B83782EA2E54 ON commandes_burger');
        $this->addSql('ALTER TABLE commandes_burger DROP burger_id, DROP commande_id');
    }
}
