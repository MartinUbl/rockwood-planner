<?php declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Http\FileUpload;

final class IssueRepository
{
	private const DefaultPerPage = 20;

	public function __construct(
		private readonly Explorer $database,
		private readonly IssueStateRepository $states,
		private readonly AttachmentStorage $attachments,
	) {
	}

	public function findByProject(
		int $projectId,
		string $sort = 'created',
		?string $stateSlug = null,
		int $page = 1,
		int $perPage = self::DefaultPerPage,
	): array
	{
		$selection = $this->filteredProjectSelection($projectId, $stateSlug);
		$selection->order($this->orderBy($sort));
		$selection->page(max(1, $page), max(1, $perPage));

		return $selection->fetchAll();
	}

	public function countByProject(int $projectId, ?string $stateSlug = null): int
	{
		return $this->filteredProjectSelection($projectId, $stateSlug)->count('*');
	}

	public function find(int $id): ?ActiveRow
	{
		return $this->database->table('issues')->get($id);
	}

	public function severityCountsForProject(int $projectId): array
	{
		$counts = [
			'low' => 0,
			'medium' => 0,
			'high' => 0,
			'critical' => 0,
		];

		$rows = $this->database->query(
			'SELECT i.severity, COUNT(*) AS total
			FROM issues i
			JOIN issue_states s ON s.id = i.state_id
			WHERE i.project_id = ? AND s.slug NOT IN (?, ?)
			GROUP BY i.severity',
			$projectId,
			'completed',
			'rejected',
		);

		foreach ($rows as $row) {
			$severity = (string) $row->offsetGet('severity');
			if (array_key_exists($severity, $counts)) {
				$counts[$severity] = (int) $row->offsetGet('total');
			}
		}

		return $counts;
	}

	public function stateCountsForProject(int $projectId): array
	{
		$counts = [
			'open' => 0,
			'completed' => 0,
			'rejected' => 0,
		];

		$rows = $this->database->query(
			'SELECT
				CASE
					WHEN s.slug = ? THEN ?
					WHEN s.slug = ? THEN ?
					ELSE ?
				END AS bucket,
				COUNT(*) AS total
			FROM issues i
			JOIN issue_states s ON s.id = i.state_id
			WHERE i.project_id = ?
			GROUP BY bucket',
			'completed',
			'completed',
			'rejected',
			'rejected',
			'open',
			$projectId,
		);

		foreach ($rows as $row) {
			$bucket = (string) $row->offsetGet('bucket');
			if (array_key_exists($bucket, $counts)) {
				$counts[$bucket] = (int) $row->offsetGet('total');
			}
		}

		return $counts;
	}

	public function create(int $projectId, int $creatorId, array $values): ActiveRow
	{
		$state = $this->states->findDefault();
		if (!$state) {
			throw new \RuntimeException('Default issue state is missing.');
		}

		$issue = $this->database->table('issues')->insert([
			'project_id' => $projectId,
			'state_id' => (int) $state->getPrimary(),
			'creator_id' => $creatorId,
			'title' => $values['title'],
			'severity' => $values['severity'],
			'body' => $values['body'],
			'updated_at' => new \DateTimeImmutable,
		]);
		if (!$issue instanceof ActiveRow) {
			throw new \RuntimeException('Issue could not be created.');
		}

		$this->replaceAttachment((int) $issue->getPrimary(), $values['attachment'] ?? null);
		return $issue;
	}

	public function update(int $id, array $values): void
	{
		$this->database->table('issues')->where('id', $id)->update([
			'title' => $values['title'],
			'severity' => $values['severity'],
			'state_id' => $values['state_id'],
			'body' => $values['body'],
			'updated_at' => new \DateTimeImmutable,
		]);

		$this->replaceAttachment($id, $values['attachment'] ?? null);
	}

	public function transitionTo(int $id, string $targetSlug): void
	{
		$state = $this->states->findBySlug($targetSlug);
		if (!$state) {
			throw new \RuntimeException('Target issue state is missing.');
		}

		$this->database->table('issues')->where('id', $id)->update([
			'state_id' => (int) $state->getPrimary(),
			'updated_at' => new \DateTimeImmutable,
		]);
	}

	public function delete(int $id): void
	{
		foreach ($this->attachmentsForIssue($id) as $attachment) {
			$this->attachments->delete((string) $attachment->offsetGet('stored_name'));
		}

		$this->database->table('issues')->where('id', $id)->delete();
	}

	public function addComment(int $issueId, int $userId, string $body): void
	{
		$this->database->transaction(function () use ($issueId, $userId, $body): void {
			$this->database->table('comments')->insert([
				'issue_id' => $issueId,
				'user_id' => $userId,
				'body' => $body,
			]);

			$this->touch($issueId);
		});
	}

	public function comments(int $issueId): array
	{
		return $this->database->table('comments')
			->where('issue_id', $issueId)
			->order('created_at DESC')
			->fetchAll();
	}

	public function attachment(int $issueId): ?ActiveRow
	{
		return $this->database->table('issue_attachments')
			->where('issue_id', $issueId)
			->fetch() ?: null;
	}

	public function attachmentById(int $id): ?ActiveRow
	{
		return $this->database->table('issue_attachments')->get($id);
	}

	private function replaceAttachment(int $issueId, mixed $upload): void
	{
		if (!$upload instanceof FileUpload || !$upload->hasFile()) {
			return;
		}

		if (!$upload->isOk()) {
			throw new \RuntimeException('Attachment upload failed.');
		}

		foreach ($this->attachmentsForIssue($issueId) as $oldAttachment) {
			$this->attachments->delete((string) $oldAttachment->offsetGet('stored_name'));
			$oldAttachment->delete();
		}

		$data = $this->attachments->store($upload);
		$data['issue_id'] = $issueId;
		$this->database->table('issue_attachments')->insert($data);
	}

	private function touch(int $issueId): void
	{
		$this->database->table('issues')->where('id', $issueId)->update([
			'updated_at' => new \DateTimeImmutable,
		]);
	}

	private function attachmentsForIssue(int $issueId): array
	{
		return $this->database->table('issue_attachments')
			->where('issue_id', $issueId)
			->fetchAll();
	}

	private function filteredProjectSelection(int $projectId, ?string $stateSlug): \Nette\Database\Table\Selection
	{
		$selection = $this->database->table('issues')->where('project_id', $projectId);
		if ($stateSlug !== null && $stateSlug !== '') {
			$state = $this->states->findBySlug($stateSlug);
			if ($state) {
				$selection->where('state_id', (int) $state->getPrimary());
			} else {
				$selection->where('1 = 0');
			}
		}

		return $selection;
	}

	private function orderBy(string $sort): string
	{
		return match ($sort) {
			'name' => 'title ASC, created_at DESC',
			'severity' => "FIELD(severity, 'critical', 'high', 'medium', 'low') ASC, created_at DESC",
			default => 'created_at DESC',
		};
	}
}
