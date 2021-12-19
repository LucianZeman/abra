<?php
namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;

final class LoginPresenter extends Nette\Application\UI\Presenter
{
    private Nette\Database\Explorer $database;

	public function __construct(Nette\Database\Explorer $database)
	{
		$this->database = $database;
	}

    public function renderDefault(){

        $this->redirect('Login:Prihlaseni');
    }

    //Form -> přihlášení uživatele

    protected function createComponentLoginForm(): Form
    {
        $form = new Form;

        $form->addText('username', '')
            ->setRequired();

        $form->addPassword('password', '')
            ->setRequired();

        $form->addSubmit('login', 'Přihlásit se');

        $form->onSuccess[] = [$this, 'loginFormSucceeded'];
        return $form;
    }

    public function loginFormSucceeded(Form $form, \stdClass $values): void
    {   
        $user = $this->getUser();
        try {
            $user->login($values->username, $values->password);
            $this->redirect("Homepage:default");
        } catch (Nette\Security\AuthenticationException $e) {
            $this->flashMessage('Uživatelské jméno nebo heslo je nesprávné');
        }
    }

    //Odhlášení uživatele

    public function renderOdhlaseni(): void {
        $user = $this->getUser();
        $user->logout();
        $this->redirect("Login:prihlaseni");
    }
}	