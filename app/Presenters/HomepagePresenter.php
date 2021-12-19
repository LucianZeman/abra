<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

final class HomepagePresenter extends Nette\Application\UI\Presenter
{
    private Nette\Database\Explorer $database;

	public function __construct(Nette\Database\Explorer $database)
	{
		$this->database = $database;
	}

    protected function startup()
     {
         parent::startup();
         if (!$this->getUser()->isLoggedIn()) {
             $this->redirect('Login:prihlaseni');
         }

         $this->getUser()->setExpiration('60 minutes');
    }

    //Render schůzek na main page

    public function renderDefault(): void
    {
        $this->template->schuzky = $this->database
            ->table('schuzky')
            ->order('datum DESC');
    }
}