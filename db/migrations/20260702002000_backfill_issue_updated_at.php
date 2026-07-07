<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class BackfillIssueUpdatedAt extends AbstractMigration
{
	public function up(): void
	{
		$this->execute('UPDATE issues SET updated_at = created_at WHERE updated_at IS NULL');
		$this->execute('CREATE INDEX idx_issues_project_updated ON issues (project_id, updated_at)');
	}

	public function down(): void
	{
		$this->execute('DROP INDEX idx_issues_project_updated ON issues');
	}
}
