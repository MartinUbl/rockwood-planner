<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateIssueTracker extends AbstractMigration
{
	public function change(): void
	{
		$this->table('users')
			->addColumn('email', 'string', ['limit' => 190])
			->addColumn('password_hash', 'string', ['limit' => 255])
			->addColumn('name', 'string', ['limit' => 120])
			->addColumn('approved_at', 'datetime', ['null' => true])
			->addColumn('approved_by', 'integer', ['null' => true, 'signed' => false])
			->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
			->addIndex(['email'], ['unique' => true])
			->create();

		$this->table('users')
			->addForeignKey('approved_by', 'users', 'id', ['delete' => 'SET_NULL'])
			->update();

		$this->table('projects')
			->addColumn('name', 'string', ['limit' => 190])
			->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
			->addColumn('updated_at', 'datetime', ['null' => true])
			->create();

		$this->table('issue_states')
			->addColumn('slug', 'string', ['limit' => 60])
			->addColumn('label', 'string', ['limit' => 80])
			->addColumn('position', 'integer', ['default' => 0])
			->addIndex(['slug'], ['unique' => true])
			->create();

		$this->table('issues')
			->addColumn('project_id', 'integer', ['signed' => false])
			->addColumn('state_id', 'integer', ['signed' => false])
			->addColumn('creator_id', 'integer', ['signed' => false])
			->addColumn('title', 'string', ['limit' => 190])
			->addColumn('severity', 'enum', ['values' => ['low', 'medium', 'high', 'critical'], 'default' => 'medium'])
			->addColumn('body', 'text')
			->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
			->addColumn('updated_at', 'datetime', ['null' => true])
			->addIndex(['project_id'])
			->addIndex(['state_id'])
			->addIndex(['creator_id'])
			->create();

		$this->table('issues')
			->addForeignKey('project_id', 'projects', 'id', ['delete' => 'CASCADE'])
			->addForeignKey('state_id', 'issue_states', 'id', ['delete' => 'RESTRICT'])
			->addForeignKey('creator_id', 'users', 'id', ['delete' => 'RESTRICT'])
			->update();

		$this->table('issue_attachments')
			->addColumn('issue_id', 'integer', ['signed' => false])
			->addColumn('original_name', 'string', ['limit' => 255])
			->addColumn('stored_name', 'string', ['limit' => 255])
			->addColumn('mime_type', 'string', ['limit' => 120, 'null' => true])
			->addColumn('size', 'integer', ['signed' => false])
			->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
			->addIndex(['issue_id'])
			->create();

		$this->table('issue_attachments')
			->addForeignKey('issue_id', 'issues', 'id', ['delete' => 'CASCADE'])
			->update();

		$this->table('comments')
			->addColumn('issue_id', 'integer', ['signed' => false])
			->addColumn('user_id', 'integer', ['signed' => false])
			->addColumn('body', 'text')
			->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
			->addIndex(['issue_id'])
			->addIndex(['user_id'])
			->create();

		$this->table('comments')
			->addForeignKey('issue_id', 'issues', 'id', ['delete' => 'CASCADE'])
			->addForeignKey('user_id', 'users', 'id', ['delete' => 'RESTRICT'])
			->update();

		$this->table('issue_states')->insert([
			['slug' => 'new', 'label' => 'New', 'position' => 10],
			['slug' => 'in_progress', 'label' => 'In progress', 'position' => 20],
			['slug' => 'needs_testing', 'label' => 'Needs testing', 'position' => 30],
			['slug' => 'completed', 'label' => 'Completed', 'position' => 40],
			['slug' => 'rejected', 'label' => 'Rejected', 'position' => 50],
		])->saveData();
	}
}
