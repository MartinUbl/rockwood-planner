<?php declare(strict_types=1);

namespace App\Presentation\Issue;

use App\Model\IssueRepository;
use App\Model\IssueStateRepository;
use App\Model\ProjectRepository;
use App\Model\UserRepository;
use App\Presentation\BasePresenter;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;

final class IssuePresenter extends BasePresenter
{
	private const AllowedTransitions = [
		'new' => ['in_progress', 'completed', 'rejected'],
		'in_progress' => ['completed', 'rejected'],
	];

	private ?int $projectId = null;
	private ?int $issueId = null;

	public function __construct(
		UserRepository $users,
		private readonly ProjectRepository $projects,
		private readonly IssueRepository $issues,
		private readonly IssueStateRepository $states,
	) {
		parent::__construct($users);
	}

	public function actionCreate(int $id): void
	{
		$project = $this->projects->find($id);
		if (!$project) {
			throw new BadRequestException('Project not found.');
		}

		$this->projectId = $id;
		$this->template->project = $project;
	}

	public function renderShow(int $id): void
	{
		$issue = $this->issues->find($id);
		if (!$issue) {
			throw new BadRequestException('Issue not found.');
		}

		$this->template->issue = $issue;
		$this->template->project = $issue->ref('projects', 'project_id');
		$this->template->attachment = $this->issues->attachment($id);
		$this->template->comments = $this->issues->comments($id);
		$this->template->availableTransitions = $this->availableTransitions($issue);
	}

	public function actionEdit(int $id): void
	{
		$issue = $this->issues->find($id);
		if (!$issue) {
			throw new BadRequestException('Issue not found.');
		}

		$this->issueId = $id;
		$this->projectId = (int) $issue->offsetGet('project_id');
		$this->template->issue = $issue;
		$this->template->project = $issue->ref('projects', 'project_id');
		$this->template->attachment = $this->issues->attachment($id);
		$this['issueForm']->setDefaults([
			'title' => $issue->offsetGet('title'),
			'severity' => $issue->offsetGet('severity'),
			'state_id' => $issue->offsetGet('state_id'),
			'body' => $issue->offsetGet('body'),
		]);
	}

	public function actionDelete(int $id): void
	{
		$issue = $this->issues->find($id);
		if (!$issue) {
			throw new BadRequestException('Issue not found.');
		}

		$projectId = (int) $issue->offsetGet('project_id');
		$this->issues->delete($id);
		$this->flashMessage('Issue deleted.', 'success');
		$this->redirect('Project:show', $projectId);
	}

	public function actionTransition(int $id, string $target): void
	{
		$issue = $this->issues->find($id);
		if (!$issue) {
			throw new BadRequestException('Issue not found.');
		}

		$currentSlug = (string) $issue->ref('issue_states', 'state_id')->offsetGet('slug');
		if (!in_array($target, self::AllowedTransitions[$currentSlug] ?? [], true)) {
			$this->flashMessage('This state transition is not available for the current issue state.', 'error');
			$this->redirect('show', $id);
		}

		$this->issues->transitionTo($id, $target);
		$this->flashMessage('Issue state updated.', 'success');
		$this->redirect('show', $id);
	}

	private function availableTransitions(\Nette\Database\Table\ActiveRow $issue): array
	{
		$currentSlug = (string) $issue->ref('issue_states', 'state_id')->offsetGet('slug');
		$labels = $this->states->pairs();
		$transitions = [];

		foreach (self::AllowedTransitions[$currentSlug] ?? [] as $targetSlug) {
			$targetState = $this->states->findBySlug($targetSlug);
			if ($targetState) {
				$targetId = (int) $targetState->getPrimary();
				$transitions[$targetSlug] = $labels[$targetId] ?? (string) $targetState->offsetGet('label');
			}
		}

		return $transitions;
	}

	protected function createComponentIssueForm(): Form
	{
		$form = new Form;
		$form->addProtection();
		$form->addText('title', 'Title')->setRequired()->setMaxLength(190);
		$form->addSelect('severity', 'Severity', [
			'low' => 'Low',
			'medium' => 'Medium',
			'high' => 'High',
			'critical' => 'Critical',
		])
			->setDefaultValue('medium')
			->setHtmlAttribute('class', 'severity-control severity-medium');
		$form->addSelect('state_id', 'State', $this->states->pairs());
		$form->addTextArea('body', 'Body')->setRequired();
		$form->addUpload('attachment', 'Attachment');
		$submitLabel = $this->issueId ? 'Save issue' : 'Create issue';
		$form->addSubmit('send', $submitLabel)
			->setHtmlAttribute('title', $submitLabel)
			->setHtmlAttribute('aria-label', $submitLabel);

		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			$data = (array) $values;

			if ($this->issueId) {
				$this->issues->update($this->issueId, $data);
				$this->flashMessage('Issue updated.', 'success');
				$this->redirect('show', $this->issueId);
			}

			if (!$this->projectId) {
				throw new \RuntimeException('Project context is missing.');
			}

			$issue = $this->issues->create($this->projectId, (int) $this->getUser()->getId(), $data);
			$this->flashMessage('Issue created.', 'success');
			$this->redirect('show', $issue->getPrimary());
		};

		return $form;
	}

	protected function createComponentCommentForm(): Form
	{
		$form = new Form;
		$form->addProtection();
		$form->addTextArea('body', 'Comment')->setRequired();
		$form->addSubmit('send', 'Add comment')
			->setHtmlAttribute('title', 'Add comment')
			->setHtmlAttribute('aria-label', 'Add comment');

		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			$issueId = (int) $this->getParameter('id');
			$this->issues->addComment($issueId, (int) $this->getUser()->getId(), $values->body);
			$this->flashMessage('Comment added.', 'success');
			$this->redirect('this');
		};

		return $form;
	}
}
