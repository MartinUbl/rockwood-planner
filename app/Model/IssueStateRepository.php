<?php declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;

final class IssueStateRepository
{
	public function __construct(private readonly Explorer $database)
	{
	}

	public function findDefault(): ?ActiveRow
	{
		return $this->database->table('issue_states')
			->where('slug', 'new')
			->fetch() ?: null;
	}

	public function findBySlug(string $slug): ?ActiveRow
	{
		return $this->database->table('issue_states')
			->where('slug', $slug)
			->fetch() ?: null;
	}

	public function pairs(): array
	{
		return $this->database->table('issue_states')
			->order('position ASC, label ASC')
			->fetchPairs('id', 'label');
	}

	public function slugPairs(): array
	{
		return $this->database->table('issue_states')
			->order('position ASC, label ASC')
			->fetchPairs('slug', 'label');
	}
}
