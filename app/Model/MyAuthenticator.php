<?php
use Nette\Security\SimpleIdentity;
use Nette\Security\Passwords;

class MyAuthenticator implements Nette\Security\Authenticator
{
	private $database;
	private $passwords;

	public function __construct(
		Nette\Database\Explorer $database,
		Nette\Security\Passwords $passwords
	) {
		$this->database = $database;
		$this->passwords = $passwords;
	}

	public function authenticate(string $username, string $password): SimpleIdentity
	{
		$row = $this->database->table('users')
			->where('username', $username)
			->fetch();

		if (!$row) {
			throw new Nette\Security\AuthenticationException('Uživatel nebyl nalezen.');
		}

		// if (!$this->passwords->verify($password, $row->password)) {
		if (!$this->passwords->verify($password, $row->password)) {
			throw new Nette\Security\AuthenticationException('Špatné heslo.');
		}

		return new SimpleIdentity(
			$row->id,
			$row->opravneni,
			['name' => $row->name, 'username' => $row->username, 'druzina' => $row->druzina,]
		);
	}
}