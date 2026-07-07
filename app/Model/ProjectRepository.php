<?php declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;

final class ProjectRepository
{
	public function __construct(private readonly Explorer $database)
	{
	}

	public function findAll(): array
	{
		return $this->database->table('projects')
			->order('name ASC')
			->fetchAll();
	}

	public function findGrouped(): array
	{
		$groups = [];
		$groupRows = $this->database->query(
			'SELECT g.id, MAX(COALESCE(i.updated_at, i.created_at)) AS last_issue_modified_at
			FROM project_groups g
			LEFT JOIN projects p ON p.group_id = g.id
			LEFT JOIN issues i ON i.project_id = p.id
			GROUP BY g.id
			ORDER BY last_issue_modified_at DESC, g.name ASC',
		);

		foreach ($groupRows as $groupRow) {
			$group = $this->database->table('project_groups')->get((int) $groupRow->offsetGet('id'));
			if (!$group) {
				continue;
			}

			$groups[] = [
				'group' => $group,
				'lastIssueModifiedAt' => $this->dateOrNull($groupRow->offsetGet('last_issue_modified_at')),
				'projects' => $this->findProjectsForGroup((int) $group->getPrimary()),
			];
		}

		return $groups;
	}

	public function find(int $id): ?ActiveRow
	{
		return $this->database->table('projects')->get($id);
	}

	public function create(string $name, int $groupId): ActiveRow
	{
		return $this->database->table('projects')->insert([
			'name' => $name,
			'group_id' => $groupId,
		]);
	}

	public function update(int $id, string $name, int $groupId): void
	{
		$this->database->table('projects')->where('id', $id)->update([
			'name' => $name,
			'group_id' => $groupId,
			'updated_at' => new \DateTimeImmutable,
		]);
	}

	public function delete(int $id): void
	{
		$this->database->table('projects')->where('id', $id)->delete();
	}

	private function findProjectsForGroup(int $groupId): array
	{
		$projects = [];
		$projectRows = $this->database->query(
			'SELECT p.id, MAX(COALESCE(i.updated_at, i.created_at)) AS last_issue_modified_at
			FROM projects p
			LEFT JOIN issues i ON i.project_id = p.id
			WHERE p.group_id = ?
			GROUP BY p.id
			ORDER BY last_issue_modified_at DESC, p.name ASC',
			$groupId,
		);

		foreach ($projectRows as $projectRow) {
			$project = $this->find((int) $projectRow->offsetGet('id'));
			if ($project) {
				$projects[] = [
					'project' => $project,
					'lastIssueModifiedAt' => $this->dateOrNull($projectRow->offsetGet('last_issue_modified_at')),
				];
			}
		}

		return $projects;
	}

	private function dateOrNull(mixed $value): ?\DateTimeImmutable
	{
		if (!$value) {
			return null;
		}

		if ($value instanceof \DateTimeInterface) {
			return \DateTimeImmutable::createFromInterface($value);
		}

		return new \DateTimeImmutable((string) $value);
	}
}
