$(function () {
	$.nette.init();
});

function createUsername()  {
	var username = $('input[class="profile-name"]').val().toLowerCase();
	
	   var name_low_text = username.normalize('NFD').replace(/[\u0300-\u036f]/g, "");
	 var name_low_finish = name_low_text.replace(/\s/g, '');
	$('input[class="profile-username"]').val('@'+name_low_finish);
   }
   
   $('input[class="profile-name"]').on('keyup', createUsername);
   $('input[class="profile-password"]').on('keyup', createUsername);