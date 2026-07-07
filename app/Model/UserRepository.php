<?php declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;

final class UserRepository implements Authenticator
{
	public function __construct(
		private readonly Explorer $database,
		private readonly Passwords $passwords,
	) {
	}

	public function authenticate(string $username, string $password): SimpleIdentity
	{
		$user = $this->findByEmail($username);
		if (!$user) {
			throw new AuthenticationException('Email or password is incorrect.', self::IdentityNotFound);
		}

		if (!$this->passwords->verify($password, (string) $user->offsetGet('password_hash'))) {
			throw new AuthenticationException('Email or password is incorrect.', self::InvalidCredential);
		}

		if (!$user->offsetGet('approved_at')) {
			throw new AuthenticationException('Your account is waiting for approval.', self::NotApproved);
		}

		return new SimpleIdentity((int) $user->getPrimary(), null, [
			'email' => (string) $user->offsetGet('email'),
			'name' => (string) $user->offsetGet('name'),
			'approved' => true,
		]);
	}

	public function register(string $email, string $name, string $password): ActiveRow
	{
		$isFirstUser = $this->database->table('users')->count('*') === 0;

		return $this->database->table('users')->insert([
			'email' => mb_strtolower($email),
			'name' => $name,
			'password_hash' => $this->passwords->hash($password),
			'approved_at' => $isFirstUser ? new \DateTimeImmutable : null,
		]);
	}

	public function findByEmail(string $email): ?ActiveRow
	{
		return $this->database->table('users')
			->where('email', mb_strtolower($email))
			->fetch() ?: null;
	}

	public function find(int $id): ?ActiveRow
	{
		return $this->database->table('users')->get($id);
	}

	public function findPending(int $exceptUserId): array
	{
		return $this->database->table('users')
			->where('approved_at', null)
			->where('id != ?', $exceptUserId)
			->order('created_at ASC')
			->fetchAll();
	}

	public function approve(int $userId, int $approverId): void
	{
		if ($userId === $approverId) {
			throw new \InvalidArgumentException('Users cannot approve themselves.');
		}

		$this->database->table('users')
			->where('id', $userId)
			->where('approved_at', null)
			->update([
				'approved_at' => new \DateTimeImmutable,
				'approved_by' => $approverId,
			]);
	}
}
