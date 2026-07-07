<?php declare(strict_types=1);

namespace App\Presentation\Home;

use Nette;


final class HomePresenter extends Nette\Application\UI\Presenter
{
	public function actionDefault(): void
	{
		$this->redirect($this->getUser()->isLoggedIn() ? 'Project:default' : 'Sign:in');
	}
}
