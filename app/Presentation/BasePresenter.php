<?php declare(strict_types=1);

namespace App\Presentation;

use App\Model\UserRepository;
use Nette\Application\UI\Presenter;

abstract class BasePresenter extends Presenter
{
	public function __construct(private readonly UserRepository $users)
	{
		parent::__construct();
	}

	protected function startup(): void
	{
		parent::startup();

		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
		}
	}

	protected function beforeRender(): void
	{
		parent::beforeRender();

		$userId = (int) $this->getUser()->getId();
		$identity = $this->getUser()->getIdentity();
		$this->template->currentUser = $identity ? $identity->getData() : [];
		$this->template->pendingUsersCount = count($this->users->findPending($userId));
	}
}
