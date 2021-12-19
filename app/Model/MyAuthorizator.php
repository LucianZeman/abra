<?php 

use Nette\Security\SimpleIdentity;
use Nette\Security\Passwords;

class MyAuthorizator implements Nette\Security\Authorizator
{
	// public function isAllowed($role, $resource, $operation): bool
	// {
	// 	if ($role === 'Admin') {
	// 		echo "Jsi Admin";
	// 	}
	// 	if ($role === 'Správce') {
	// 		echo "Jsi Správce";
	// 	}

    //     if ($role === 'Rádce') {
	// 		echo "Jsi Rádce";
	// 	}

    //     if ($role === 'Uživatel') {
	// 		echo "Jsi Uživatel";
	// 	}

	// 	return false;
	// }
}