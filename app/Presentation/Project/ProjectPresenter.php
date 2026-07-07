<?php declare(strict_types=1);

namespace App\Presentation\Project;

use App\Model\IssueRepository;
use App\Model\IssueStateRepository;
use App\Model\ProjectGroupRepository;
use App\Model\ProjectRepository;
use App\Model\UserRepository;
use App\Presentation\BasePresenter;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;

final class ProjectPresenter extends BasePresenter
{
	private const IssuesPerPage = 20;
	private const SortOptions = [
		'created' => 'Creation date',
		'name' => 'Name',
		'severity' => 'Severity',
	];

	private ?int $projectId = null;

	public function __construct(
		UserRepository $users,
		private readonly ProjectRepository $projects,
		private readonly ProjectGroupRepository $groups,
		private readonly IssueRepository $issues,
		private readonly IssueStateRepository $states,
	) {
		parent::__construct($users);
	}

	public function renderDefault(): void
	{
		$groupedProjects = $this->projects->findGrouped();
		$this->template->groupedProjects = $groupedProjects;
		$this->template->totalProjects = array_sum(array_map(
			static fn (array $group): int => count($group['projects']),
			$groupedProjects,
		));
	}

	public function renderShow(int $id, string $sort = 'created', ?string $state = null, int $page = 1): void
	{
		$project = $this->projects->find($id);
		if (!$project) {
			throw new BadRequestException('Project not found.');
		}

		$sort = array_key_exists($sort, self::SortOptions) ? $sort : 'created';
		$stateOptions = $this->states->slugPairs();
		$state = $state !== null && array_key_exists($state, $stateOptions) ? $state : null;
		$totalIssues = $this->issues->countByProject($id, $state);
		$lastPage = max(1, (int) ceil($totalIssues / self::IssuesPerPage));
		$page = min(max(1, $page), $lastPage);

		$this->template->project = $project;
		$this->template->issues = $this->issues->findByProject($id, $sort, $state, $page, self::IssuesPerPage);
		$this->template->severityCounts = $this->issues->severityCountsForProject($id);
		$this->template->stateCounts = $this->issues->stateCountsForProject($id);
		$this->template->sortOptions = self::SortOptions;
		$this->template->stateOptions = $stateOptions;
		$this->template->selectedSort = $sort;
		$this->template->selectedState = $state;
		$this->template->page = $page;
		$this->template->lastPage = $lastPage;
		$this->template->totalIssues = $totalIssues;
		$this->template->issuesPerPage = self::IssuesPerPage;
	}

	public function actionEdit(int $id): void
	{
		$project = $this->projects->find($id);
		if (!$project) {
			throw new BadRequestException('Project not found.');
		}

		$this->projectId = $id;
		$this['projectForm']->setDefaults([
			'name' => $project->offsetGet('name'),
			'group_id' => $project->offsetGet('group_id'),
		]);
	}

	public function actionDelete(int $id): void
	{
		$this->projects->delete($id);
		$this->flashMessage('Project deleted.', 'success');
		$this->redirect('default');
	}

	protected function createComponentProjectForm(): Form
	{
		$form = new Form;
		$form->addProtection();
		$form->addText('name', 'Project name')->setRequired()->setMaxLength(190);
		$form->addSelect('group_id', 'Group', $this->groups->pairs())
			->setRequired('Choose an existing group or create a new one below.');
		$form->addText('new_group', 'New group')
			->setNullable()
			->setMaxLength(190)
			->setHtmlAttribute('placeholder', 'Optional');

		if (!$this->projectId && ($defaultGroup = $this->groups->findDefault())) {
			$form->setDefaults([
				'group_id' => $defaultGroup->getPrimary(),
			]);
		}

		$submitLabel = $this->projectId ? 'Save project' : 'Create project';
		$form->addSubmit('send', $submitLabel)
			->setHtmlAttribute('title', $submitLabel)
			->setHtmlAttribute('aria-label', $submitLabel);

		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			$groupId = $this->resolveGroupId($values);
			if ($this->projectId) {
				$this->projects->update($this->projectId, $values->name, $groupId);
				$this->flashMessage('Project updated.', 'success');
				$this->redirect('show', $this->projectId);
			}

			$project = $this->projects->create($values->name, $groupId);
			$this->flashMessage('Project created.', 'success');
			$this->redirect('show', $project->getPrimary());
		};

		return $form;
	}

	private function resolveGroupId(\stdClass $values): int
	{
		$newGroup = trim((string) ($values->new_group ?? ''));
		if ($newGroup !== '') {
			return (int) $this->groups->findOrCreate($newGroup)->getPrimary();
		}

		return (int) $values->group_id;
	}
}
