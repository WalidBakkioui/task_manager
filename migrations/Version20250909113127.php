<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250909113127 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout table task_group, liaison task.group_id, et due_date NOT NULL (avec correction des anciennes données).';
    }

    public function up(Schema $schema): void
    {
        // DBAL 3.x
        $sm = $this->connection->createSchemaManager();

        // 1) Créer task_group si elle n’existe pas
        if (!$sm->tablesExist(['task_group'])) {
            $this->addSql("
                CREATE TABLE task_group (
                    id INT AUTO_INCREMENT NOT NULL,
                    user_id INT NOT NULL,
                    name VARCHAR(64) NOT NULL,
                    color VARCHAR(7) DEFAULT NULL,
                    description LONGTEXT DEFAULT NULL,
                    created_at DATETIME NOT NULL,
                    INDEX IDX_AA645FE5A76ED395 (user_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4
                  COLLATE `utf8mb4_unicode_ci`
                  ENGINE = InnoDB
            ");
            $this->addSql("
                ALTER TABLE task_group
                ADD CONSTRAINT FK_AA645FE5A76ED395
                FOREIGN KEY (user_id) REFERENCES user (id)
            ");
        }

        // 2) Ajouter group_id à task (si absent), avec index + FK
        $taskColumns = array_change_key_case($sm->listTableColumns('task'));
        if (!isset($taskColumns['group_id'])) {
            $this->addSql("ALTER TABLE task ADD group_id INT DEFAULT NULL");

            // Créer l’index si absent
            $indexes = $sm->listTableIndexes('task');
            $hasGroupIdx = false;
            foreach ($indexes as $idx) {
                if ($idx->getName() === 'IDX_TASK_GROUP_ID' || $idx->getName() === 'IDX_527EDB25FE54D947') {
                    $hasGroupIdx = true;
                    break;
                }
            }
            if (!$hasGroupIdx) {
                $this->addSql("CREATE INDEX IDX_TASK_GROUP_ID ON task (group_id)");
            }

            // Créer la FK si absente
            $fks = $sm->listTableForeignKeys('task');
            $hasGroupFk = false;
            foreach ($fks as $fk) {
                if (in_array('group_id', array_map('strtolower', $fk->getLocalColumns()), true)) {
                    $hasGroupFk = true;
                    break;
                }
            }
            if (!$hasGroupFk) {
                $this->addSql("
                    ALTER TABLE task
                    ADD CONSTRAINT FK_TASK_GROUP_ID
                    FOREIGN KEY (group_id) REFERENCES task_group (id)
                    ON DELETE SET NULL
                ");
            }
        }

        // 3) Corriger les données de due_date AVANT de passer NOT NULL
        $this->addSql("
            UPDATE task
               SET due_date = CURDATE()
             WHERE due_date IS NULL
                OR due_date = '0000-00-00'
        ");

        // 4) Passer due_date en NOT NULL si nécessaire
        $taskColumns = array_change_key_case($sm->listTableColumns('task')); // rechargé
        if (isset($taskColumns['due_date']) && !$taskColumns['due_date']->getNotnull()) {
            $this->addSql("ALTER TABLE task CHANGE due_date due_date DATE NOT NULL");
        }
    }

    public function down(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        // Repasser due_date nullable si elle est NOT NULL
        if ($sm->tablesExist(['task'])) {
            $taskColumns = array_change_key_case($sm->listTableColumns('task'));
            if (isset($taskColumns['due_date']) && $taskColumns['due_date']->getNotnull()) {
                $this->addSql("ALTER TABLE task CHANGE due_date due_date DATE DEFAULT NULL");
            }

            // Supprimer la FK task.group_id si elle existe
            $fks = $sm->listTableForeignKeys('task');
            foreach ($fks as $fk) {
                if (in_array('group_id', array_map('strtolower', $fk->getLocalColumns()), true)) {
                    $this->addSql(sprintf('ALTER TABLE task DROP FOREIGN KEY %s', $fk->getName()));
                }
            }

            // Supprimer l’index group_id si présent
            $indexes = $sm->listTableIndexes('task');
            foreach ($indexes as $idx) {
                if (in_array(strtolower('group_id'), array_map('strtolower', $idx->getColumns()), true)) {
                    $this->addSql(sprintf('DROP INDEX %s ON task', $idx->getName()));
                }
            }

            // Supprimer la colonne group_id si présente
            $taskColumns = array_change_key_case($sm->listTableColumns('task'));
            if (isset($taskColumns['group_id'])) {
                $this->addSql("ALTER TABLE task DROP group_id");
            }
        }

        // Supprimer la table task_group si elle existe
        if ($sm->tablesExist(['task_group'])) {
            // Drop FK côté task_group si présente (généralement pas nécessaire côté down, par prudence)
            // (MySQL supprimera les FKs dépendantes quand on a supprimé celles de task ci-dessus)
            $this->addSql("DROP TABLE task_group");
        }
    }
}
