<?php declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;

final class ProjectGroupRepository
{
	public function __construct(private readonly Explorer $database)
	{
	}

	public function findAll(): array
	{
		return $this->database->table('project_groups')
			->order('name ASC')
			->fetchAll();
	}

	public function pairs(): array
	{
		return $this->database->table('project_groups')
			->order('name ASC')
			->fetchPairs('id', 'name');
	}

	public function findDefault(): ?ActiveRow
	{
		return $this->database->table('project_groups')
			->where('name', 'General')
			->fetch() ?: null;
	}

	public function findOrCreate(string $name): ActiveRow
	{
		$name = trim($name);
		if ($name === '') {
			throw new \InvalidArgumentException('Group name cannot be empty.');
		}

		$existing = $this->database->table('project_groups')
			->where('name', $name)
			->fetch();
		if ($existing) {
			return $existing;
		}

		return $this->database->table('project_groups')->insert([
			'name' => $name,
		]);
	}
}
