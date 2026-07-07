<?php declare(strict_types=1);

namespace App\Presentation\Sign;

use App\Model\UserRepository;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Security\AuthenticationException;

final class SignPresenter extends Presenter
{
	public function __construct(private readonly UserRepository $users)
	{
		parent::__construct();
	}

	public function actionOut(): void
	{
		$this->getUser()->logout(true);
		$this->flashMessage('You have been signed out.', 'success');
		$this->redirect('in');
	}

	protected function createComponentSignInForm(): Form
	{
		$form = new Form;
		$form->addProtection();
		$form->addEmail('email', 'Email')->setRequired();
		$form->addPassword('password', 'Password')->setRequired();
		$form->addSubmit('send', 'Sign in')
			->setHtmlAttribute('title', 'Sign in to Rockwood Planner')
			->setHtmlAttribute('aria-label', 'Sign in to Rockwood Planner');

		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			try {
				$this->getUser()->login($values->email, $values->password);
				$backlink = $this->getParameter('backlink');
				if (is_string($backlink)) {
					$this->restoreRequest($backlink);
				}

				$this->redirect('Project:default');
			} catch (AuthenticationException $e) {
				$form->addError($e->getMessage());
			}
		};

		return $form;
	}

	protected function createComponentRegisterForm(): Form
	{
		$form = new Form;
		$form->addProtection();
		$form->addText('name', 'Name')->setRequired()->setMaxLength(120);
		$form->addEmail('email', 'Email')->setRequired()->setMaxLength(190);
		$form->addPassword('password', 'Password')
			->setRequired()
			->addRule($form::MinLength, 'Use at least %d characters.', 8);
		$form->addPassword('passwordVerify', 'Repeat password')
			->setRequired()
			->addRule($form::Equal, 'Passwords must match.', $form['password']);
		$form->addSubmit('send', 'Create account')
			->setHtmlAttribute('title', 'Create a new Rockwood Planner account')
			->setHtmlAttribute('aria-label', 'Create a new Rockwood Planner account');

		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			try {
				$user = $this->users->register($values->email, $values->name, $values->password);
			} catch (UniqueConstraintViolationException) {
				$form->addError('This email is already registered.');
				return;
			}

			if ($user->offsetGet('approved_at')) {
				$this->flashMessage('First account created and approved. You can sign in now.', 'success');
			} else {
				$this->flashMessage('Account created. Another approved user must approve it before you can sign in.', 'info');
			}

			$this->redirect('in');
		};

		return $form;
	}
}
