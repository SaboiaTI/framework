/*
 * main.js
 * funções utilizadas no artifact login
 * @author Saboia Tecnologia da Informação <relacionamento@saboia.com.br>
 * @version 2.0
**/

var auth = new Authentication();
var bmw = new BloopModalWindow();



this.openSystem = function() {
	window.location = "/modules/request/list/";
}

this.showMaintenanceMessage = function(){
	
	$("#login, #password").css({"border-color":"#F90"});
	$("#login").val("");
	$("#password").val("");
	$("#message").text("SISTEMA EM MANUTENÇÃO. POR FAVOR TENTE NOVAMENTE MAIS TARDE.");
	
	(function(){
		if ( !(/modules\/login/.test(window.location)) ) {
			setTimeout(function(){
				window.location = '/';
			}, 5000);
		}
	})();
}

this.showInvalidLoginMessage = function(){
	$("#login, #password").css({"border-color":"#F90"});
	$("#message").text("LOGIN INVÁLIDO. POR FAVOR VERIFIQUE SEU USUÁRIO E SENHA.");
}

this.generatePassword = function() {
	
	if ($.trim($("form#form-forgot-pass").find("input#login").val()) == '') {
		return false;
	}
	
	$.ajax({
			type: 		"POST",
			url: 		"main.php",
			dataType: 	"json",
			data: 		{
							"action":"generatePassword",
							"sLogon":$.trim($("form#form-forgot-pass").find("input#login").val()),
							"sPassword":generateValidPassword()
						},
			success:	function(data) {
							
							if (data.success == true) {
								
								$('#form-forgot-pass').slideUp(150);
								$('#form-change-pass').slideUp(150);
								$('#form-login').slideDown(150);
								
								bmw.resetModal();
								bmw.modalType 		= "message";
								bmw.modalPriority 	= "success";
								bmw.modalTitle 		= "Sucesso";
								bmw.modalMessage	= "A nova senha foi gerada com sucesso.<br>Um email com a nova senha foi enviado para o usuário, no email cadastrado.";
								bmw.outputModal();
								
							} else {
								
								bmw.resetModal();
								bmw.modalType 		= "message";
								bmw.modalPriority 	= "critical";
								bmw.modalTitle 		= m('MSG_ERROR');
								bmw.modalMessage 	= "Não foi possível alterar a senha para o usuário.<br>Por favor, verifique seu login e tente novamente.";
								bmw.outputModal();
								
							}
						}
			});
	
}




this.checkLogin = function() {
	
	auth.login( $.trim($("#login").val()), $.trim($("#password").val()), 
		openSystem, 
		showInvalidLoginMessage, 
		showMaintenanceMessage
	);
	
}



$('a#forgot-pass').bind('click', function(event){
	event.preventDefault();
	$('#form-login').slideUp(150);
	$('#form-change-pass').slideUp(150);
	$('div.notice').slideDown(150);
	$('#form-forgot-pass').slideDown(150);
});

$('#form-forgot-pass').bind('reset', function(event){
	$('#form-change-pass').slideUp(150);
	$('#form-forgot-pass').slideUp(150);
	$('div.notice').slideDown(150);
	$('#form-login').slideDown(150);
});






// suport for 'placeholder'
jQuery(function() {
	jQuery.support.placeholder = false;
	test = document.createElement('input');
	if('placeholder' in test) jQuery.support.placeholder = true;
});
$(function() {
	if(!$.support.placeholder) { 
		var active = document.activeElement;
		$(':text,:password').focus(function () {
			if ($(this).attr('placeholder') != '' && $(this).val() == $(this).attr('placeholder')) {
				$(this).val('').removeClass('hasPlaceholder');
			}
		}).blur(function () {
			if ($(this).attr('placeholder') != '' && ($(this).val() == '' || $(this).val() == $(this).attr('placeholder'))) {
				$(this).val($(this).attr('placeholder')).addClass('hasPlaceholder');
			}
		});
		$(':text,:password').blur();
		$(active).focus();
		$('form').submit(function () {
			$(this).find('.hasPlaceholder').each(function() { $(this).val(''); });
		});
	}
});
