<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddProjectGroups extends AbstractMigration
{
	public function up(): void
	{
		$this->execute("
			CREATE TABLE project_groups (
				id INT UNSIGNED AUTO_INCREMENT NOT NULL,
				name VARCHAR(190) NOT NULL,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY uq_project_groups_name (name)
			) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
		");

		$this->execute("INSERT INTO project_groups (name) VALUES ('General')");
		$this->execute('ALTER TABLE projects ADD group_id INT UNSIGNED NULL AFTER id');
		$this->execute("UPDATE projects SET group_id = (SELECT id FROM project_groups WHERE name = 'General' LIMIT 1)");
		$this->execute('ALTER TABLE projects MODIFY group_id INT UNSIGNED NOT NULL');
		$this->execute('ALTER TABLE projects ADD CONSTRAINT fk_projects_group_id FOREIGN KEY (group_id) REFERENCES project_groups (id) ON DELETE RESTRICT');
		$this->execute('CREATE INDEX idx_projects_group_id ON projects (group_id)');
	}

	public function down(): void
	{
		$this->execute('ALTER TABLE projects DROP FOREIGN KEY fk_projects_group_id');
		$this->execute('DROP INDEX idx_projects_group_id ON projects');
		$this->execute('ALTER TABLE projects DROP COLUMN group_id');
		$this->execute('DROP TABLE project_groups');
	}
}
