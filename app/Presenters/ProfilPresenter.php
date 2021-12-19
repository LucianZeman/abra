<?php
namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\Image;
use Nette\Utils\Random;

final class ProfilPresenter extends Nette\Application\UI\Presenter
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
    }

    public function renderDefault(){

        $this->redirect('Profil:mujprofil');
    }

    public function renderMujprofil() {
        $user = $this->getUser();
        $userId = $user->getIdentity()->id;

        $this->template->uzivatele = $this->database
            ->table('users')
            ->where('id', $userId)
            ->Fetch();
    }

    public function renderEditprofil() {
        $user = $this->getUser();
        $userId = $user->getIdentity()->id;

        $this->template->uzivatele = $this->database
            ->table('users')
            ->where('id', $userId)
            ->Fetch();
    }

    protected function createComponentEditProfileForm(): Form
    {

        $user = $this->getUser();
        $userId = $user->getIdentity()->id;

        $userPopis = $this->database
            ->table('users')
            ->where('id', $userId)
            ->Fetch();

        $form = new Form;

        // $form->addUpload('avatar', 'Avatar:')
        //     ->addRule($form::IMAGE, 'Avatar musí být JPEG, PNG nebo GIF.')
        //     ->addRule($form::MAX_FILE_SIZE, 'Maximální velikost je 1 MB.', 1024 * 1024);

        $form->addTextArea('popis', '')
            ->setDefaultValue($userPopis->popis);

        $form->addSubmit('send', 'Uložit');

        $form->onSuccess[] = [$this, 'editProfileFormSucceeded'];
        return $form;
    }

    public function editProfileFormSucceeded(\stdClass $values): void
    {   
        $user = $this->getUser();
        $userId = $user->getIdentity()->id;

        if($userId) {
            $this->database->query("UPDATE users SET", [
                'popis' => $values->popis,
            ], 'WHERE id = ?', $userId);
        }

        // if($values->avatar->hasFile()) {
        //     //$random = Random::generate(10);

        //     $path = "../img/avatars//" . $values->avatar->getName();
        //     $values->file->move($path);
        // } else {
        //     echo "Chyba";
        // }

        $this->redirect('Profil:mujprofil');
    }

    public function renderProfil(int $userId) {

        $this->template->uzivatele = $this->database
            ->table('users')
            ->where('id', $userId)
            ->Fetch();

        $userexist = $this->database
        ->table('users')
        ->get($userId);

        if (!$userexist) {
            $this->flashMessage('Uživatel s tímto ID neexistuje.');
            $this->redirect('Homepage:default');
        }
    }
}