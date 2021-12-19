<?php
namespace App\Presenters;

use Nette;
use Nette\Utils\Arrays;
use Nette\Application\UI\Form;
use Nextras\Forms\Bridges\NetteDI\FormsExtension;

final class SchuzkyPresenter extends Nette\Application\UI\Presenter
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


    //Vypsání schůzek na hlavní page 
    public function renderDefault(){

        $this->redirect('Homepage:default');
    }

    //Nazání schůzek

    public function renderDelete(int $postId = null, string $delete = null): void
    {   
        if ($this->getUser()->isInRole('Uživatel') || $this->getUser()->isInRole('Rádce')) {
            $this->flashMessage('Na tohle nemáš oprávnění.');
            $this->redirect('Homepage:default');
        }

        $this->template->post = $this->database
			->table('schuzky')
			->get($postId);

        if($delete) {
            $this->database->query("DELETE FROM schuzky WHERE id=$postId");
            $this->database->query("DELETE FROM dochazka WHERE schuzkaid=$postId");
            $this->redirect("Homepage:default");
        }

    }        

    //Editování příspěvků

    public function renderEdit(int $postId = null, string $submit = null): void
    {   
        if ($this->getUser()->isInRole('Uživatel') || $this->getUser()->isInRole('Rádce')) {
            $this->flashMessage('Na tohle nemáš oprávnění.');
            $this->redirect('Homepage:default');
        }

        $this->template->post = $this->database
			->table('schuzky')
			->get($postId);

            // $this->template->post = $this->database
			// ->table('schuzky')
			// ->get($postId);
    }

    //Editace příspěvků -> form

    protected function createComponentEditForm(): Form
    {
        $httpRequest = $this->getHttpRequest();
        $url = $httpRequest->getUrl();
        $url = explode('/edit?postId=', $url);
        $postId = end($url);

        $post = $this->database
            ->table('schuzky')
            ->get($postId);

        $form = new Form;

        $form->addText('name', '')
            ->setRequired();

        $form->addText('date', '')
            ->setType('date')
            ->setDefaultValue($post->datum->format('Y-m-d'))
            ->setRequired();

        $form->addTextArea('content', '')
            ->setDefaultValue($post->popis)
            ->setRequired();

        $form->addSubmit('send', 'Potvrdit úpravy');

        $form->onSuccess[] = [$this, 'editFormSucceeded'];
        return $form;
    }

    //Editace příspěvků -> Odesílání dat

    public function editFormSucceeded(\stdClass $values): void
    {   
        if ($this->getUser()->isInRole('Uživatel') || $this->getUser()->isInRole('Rádce')) {
            $this->flashMessage('Na tohle nemáš oprávnění.');
            $this->redirect('Homepage:default');
        }

        $httpRequest = $this->getHttpRequest();
        $url = $httpRequest->getUrl();
        $url = explode('/edit?postId=', $url);
        $postId = end($url);

        if($postId) {
            $this->database->query("UPDATE schuzky SET", [
                'nazev' => $values->name,
                'datum' => $values->date,
                'popis' => $values->content,
            ], 'WHERE id = ?', $postId);
        }

        $this->redirect('Schuzky:schuzka', $postId);
    }
    
    public function renderLock(int $postId, int $lock) {

        if($lock == 0 || $lock == 1) {
            if ($this->getUser()->isInRole('Uživatel') || $this->getUser()->isInRole('Rádce')) {
                $this->flashMessage('Na tohle nemáš oprávnění.');
                $this->redirect('Homepage:default');
            }

            $this->database->query("UPDATE schuzky SET", [
                'lock' => $lock,
            ], 'WHERE id = ?', $postId);

            $this->redirect('Schuzky:schuzka', $postId);
        }

        // $this->redirect('Homepage:default');
    }

    public function renderSchuzka(int $postId, string $userId = null, int $value = null, int $refresh = null): void
	{
        //vypisování lock id
        $lock = $this->database
			->table('schuzky')
			->get($postId);

        //vypsání určité schůzky 

		$this->template->post = $this->database
			->table('schuzky')
			->get($postId);

        //určování id schůzky          

        $this->template->dochazka = $this->database
            ->table('dochazka')
            ->where('schuzkaid', $postId)
            ->Fetch();

        //vypsání uživatelů

        $this->template->uzivatele = $this->database
            ->table('users')
            ->order('opravneni DESC');

        //vypsání statusu uživatelů

        if($userId) {
            if ($this->getUser()->isInRole('Uživatel') || $this->getUser()->isInRole('Rádce')) {
                $this->flashMessage('Na tohle nemáš oprávnění.');
                $this->redirect('Homepage:default');
            } 
            elseif($lock->lock == 1)
            {      
                $this->flashMessage('Schůzka je uzavřená, je nutné jí odemknout.');
                $this->redirect('Homepage:default');

            } else {
                $dochazka = $this->database->query("UPDATE dochazka SET", [
                    $userId => $value,
                ], 'WHERE schuzkaid = ?', $postId);
                $this->redirect('Schuzky:schuzka', $postId);
            }
        }

        //Kontrola příspěvků v databázi

        $schuzka = $this->database
		->table('schuzky')
		->get($postId);

        if (!$schuzka) {
            $this->flashMessage('Schůzka s tímto ID neexistuje.');
            $this->redirect('Homepage:default');
        }
    }

    //Generace všech příspěvků

    // public function renderPrehled(): void
	// {
	// 	$this->template->schuzky = $this->database
    //         ->table('schuzky')
    //         ->order('datum DESC');
    // }

    //Vytvoření nové schůzky

    protected function createComponentCreateForm(): Form
    {
        $form = new Form;

        $form->addText('name', 'Název:')
            ->setRequired();

        $form->addText('date', 'Datum:')
            ->setType('date')
            ->setRequired();

        $form->addTextArea('content', 'Popis:')
            ->setRequired();

        $form->addSubmit('send', 'Vytvořit');

        $form->onSuccess[] = [$this, 'createFormSucceeded'];
        return $form;
    }

    //Odeslání dat z formuláře => vytvoření schůzky
    public function createFormSucceeded(\stdClass $values): void
    {   

        if ($this->getUser()->isInRole('Uživatel') || $this->getUser()->isInRole('Rádce')) {
            $this->flashMessage('Na tohle nemáš oprávnění.');
            $this->redirect('Homepage:default');
        }

        $this->database->table('schuzky')->insert([
            'id' => null,
            'nazev' => $values->name,
            'datum' => $values->date,
            'popis' => $values->content,
            'vytvoril' => $this->getUser()->getIdentity()->name,
        ]);

        $hodnota = $this->database
            ->table('schuzky')
            ->where('popis', $values->content);
        
        foreach($hodnota as $hodno) {
            $this->database->table('dochazka')->insert([
                'schuzkaid' => $hodno->id,
            ]);
        }
        $this->redirect('Schuzky:schuzka', $hodno->id);
    }
}