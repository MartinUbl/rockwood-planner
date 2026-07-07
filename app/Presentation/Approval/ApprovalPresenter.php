<?php declare(strict_types=1);

namespace App\Presentation\Approval;

use App\Model\UserRepository;
use App\Presentation\BasePresenter;

final class ApprovalPresenter extends BasePresenter
{
	public function __construct(private readonly UserRepository $users)
	{
		parent::__construct($users);
	}

	public function renderDefault(): void
	{
		$this->template->pendingUsers = $this->users->findPending((int) $this->getUser()->getId());
	}

	public function actionApprove(int $id): void
	{
		try {
			$this->users->approve($id, (int) $this->getUser()->getId());
			$this->flashMessage('User approved.', 'success');
		} catch (\InvalidArgumentException $e) {
			$this->flashMessage($e->getMessage(), 'error');
		}

		$this->redirect('default');
	}
}
