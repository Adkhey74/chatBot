<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250519132206 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment ADD user_id INT NOT NULL, ADD car_operation_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8441D758A6B FOREIGN KEY (car_operation_id) REFERENCES car_operation (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FE38F844A76ED395 ON appointment (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FE38F8441D758A6B ON appointment (car_operation_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F8441D758A6B
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_FE38F844A76ED395 ON appointment
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_FE38F8441D758A6B ON appointment
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE appointment DROP user_id, DROP car_operation_id
        SQL);
    }
}
