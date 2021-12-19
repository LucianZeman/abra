<?php
namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;

final class UzivatelePresenter extends Nette\Application\UI\Presenter
{
    private Nette\Database\Explorer $database;

	public function __construct(Nette\Database\Explorer $database)
	{
		$this->database = $database;
	}

    //Ověření loginu

    protected function startup()
     {
        parent::startup();
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Login:prihlaseni');
        }

        if ($this->getUser()->isInRole('Uživatel') || $this->getUser()->isInRole('Rádce') || $this->getUser()->isInRole('Správce')) {
        // if ($this->getUser()->isInRole('Uživatel') || $this->getUser()->isInRole('Rádce')) {
            $this->flashMessage('Na tohle nemáš oprávnění.');
            $this->redirect('Homepage:default');
        }
    }

    public function renderDefault(){

        $this->redirect('Uzivatele:prehled');
    }

    public function renderPrehled(){

        $this->template->uzivatele = $this->database
            ->table('users')
            ->order('id');
    }

    public function renderEdit(int $userId){

        $this->template->use = $this->database
			->table('users')
			->get($userId);

        $userexist = $this->database
        ->table('users')
        ->get($userId);

        if (!$userexist) {
            $this->flashMessage('Uživatel s tímto ID neexistuje.');
        }
    }

    //Edit user form

    protected function createComponentEditUser(): Form
    {
        $httpRequest = $this->getHttpRequest();
        $url = $httpRequest->getUrl();
        $url = explode('/edit?userId=', $url);
        $userId = end($url);

        $userexist = $this->database
        ->table('users')
        ->get($userId);

        $uzivatel = $this->database
            ->table('users')
            ->where('id', $userId)
            ->Fetch();

        $form = new Form;

        $form->addText('name', '')
            ->setDefaultValue($uzivatel->name)
            ->setRequired('Vyplň jméno');

        $form->addText('username', '')
            ->setDefaultValue('@'.$uzivatel->username)
            ->setRequired('Vyplň username');

        $form->addTextArea('popis', '')
            ->setDefaultValue($uzivatel->popis);

        $opravneni_list = [
            'Uživatel' => 'Uživatel',
            'Rádce' => 'Rádce',
            'Správce' => 'Správce',
            'Admin' => 'Admin',
        ];

        $form->addSelect('opravneni', '', $opravneni_list)
            ->setDefaultValue($uzivatel->opravneni);

        $druzina_list = [
            'Medvěd' => 'Medvěd',
            'Jelen' => 'Jelen',
        ];

        $form->addSelect('druzina', '', $druzina_list)
            ->setDefaultValue($uzivatel->druzina);

        $form->addSubmit('send', 'Uložit');

        $form->onSuccess[] = [$this, 'editUserSucceeded'];
        return $form;
    }

    public function editUserSucceeded(\stdClass $values): void
    {   
        $httpRequest = $this->getHttpRequest();
        $url = $httpRequest->getUrl();
        $url = explode('/edit?userId=', $url);
        $userId = end($url);

        $name_last = $this->database
            ->table('users')
            ->where('id', $userId)
            ->Fetch(); 

        $at = $values->username;
        $at = explode('@', $at);
        $username = end($at);
        
        $uzivatel_name = $this->database
            ->table('users')
            ->where('username', $username)
            ->Fetch();

        $uzivatel_dupli = $this->database
            ->table('users')
            ->where('id', $userId)
            ->Fetch();

        if(empty($uzivatel_name) || ($uzivatel_dupli->username == $uzivatel_name->username)) {
            $this->database->query("ALTER TABLE `dochazka` CHANGE `$name_last->username` `$username` INT(11) NOT NULL DEFAULT '0'");

            $this->database->query("UPDATE users SET", [
                'name' => $values->name,
                'username' => $username,
                'popis' => $values->popis,
                'opravneni' => $values->opravneni,
                'druzina' => $values->druzina,
            ], 'WHERE id = ?', $userId);

            $this->redirect('Uzivatele:prehled');
            $this->flashMessage('Debug.');
        } else {
            $this->flashMessage('Uživatelské jméno již existuje.');
        }
        
    }

    //New user form

    protected function createComponentNewUser(): Form
    {
        $form = new Form;

        $form->addText('name', '')
            ->setRequired('Vyplň jméno');

        $form->addText('username', '')
            ->setRequired('Vyplň username');

        $form->addPassword('password', '')
            ->setRequired('Vyplň heslo');

        $form->addTextArea('popis', '');

        $opravneni_list = [
            'Uživatel' => 'Uživatel',
            'Rádce' => 'Rádce',
            'Správce' => 'Správce',
            'Admin' => 'Admin',
        ];

        $form->addSelect('opravneni', '', $opravneni_list)
            ->setDefaultValue('Uživatel');

        $druzina_list = [
            'Medvěd' => 'Medvěd',
            'Jelen' => 'Jelen',
        ];

        $form->addSelect('druzina', '', $druzina_list)
        ->setDefaultValue('Medvěd');

        $form->addSubmit('send', 'Vytvořit');

        $form->onSuccess[] = [$this, 'newUserSucceeded'];
        return $form;
    }

    public function newUserSucceeded(\stdClass $values): void
    {   
        $hashpass = password_hash($values->password, PASSWORD_DEFAULT);

        $at = $values->username;
        $at = explode('@', $at);
        $username = end($at);

        $uzivatel_name = $this->database
            ->table('users')
            ->where('username', $username)
            ->Fetch();

        if(empty($uzivatel_name)) {
            $this->database->table('users')->insert([
                'id' => null,
                'name' => $values->name,
                'username' => $username,
                'password' => $hashpass,
                'popis' => $values->popis,
                'opravneni' => $values->opravneni,
                'druzina' => $values->druzina,
            ]);

            $this->database->query("ALTER TABLE `dochazka` ADD `$username` INT(11) NOT NULL DEFAULT '0'");

            $this->redirect('Uzivatele:prehled');
        } else {
            $this->flashMessage('Uživatelské jméno již existuje.');
        }
    }

    //mazání uživatelů

    public function renderDelete(int $userId = null, string $submit = null){

        $this->template->uzivatel = $this->database
            ->table('users')
            ->where('id', $userId)
            ->Fetch();

        $uzivatel_name = $this->database
            ->table('users')
            ->where('id', $userId)
            ->Fetch();

        if($submit) {
            if ($this->getUser()->isInRole('Uživatel') || $this->getUser()->isInRole('Rádce')) {
                $this->flashMessage('Na tohle nemáš oprávnění.');
                $this->redirect('Homepage:default');
            }
            $this->database->query("ALTER TABLE `dochazka` DROP `$uzivatel_name->username`");
            $this->database->query("DELETE FROM users WHERE id=$userId");
            $this->redirect('Uzivatele:prehled');
        }

    }
}