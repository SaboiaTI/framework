/* ------------------------------------------------------------------------------------------------------
 * basicBehavior.js
 * 
 * comportamentos comuns �s p�ginas b�sicas do framework bloop
 * @author 			Saboia Tecnologia da Informa��o <relacionamento@saboia.com.br>
 * @link			http://www.saboia.com.br
 * @version 		1.0
 * @dependencies	jQuery
 * ------------------------------------------------------------------------------------------------------
*/





/* ------------------------------------------------------------------------------------------------------
 * USER NAVIGATION MENU
*/

$('button#user-information').unbind('click');
$('button#user-information').bind('click', function(event){
	event.preventDefault();
	event.stopPropagation();
	$('nav#user-navigation').stop(true,true).slideToggle(100, "easeInOutQuart");
});

$('body').bind('click', function(event) {
	$('nav#user-navigation').stop(true,true).slideUp(100, "easeInOutQuart");
});

$('nav#user-navigation ul li a').unbind('click');
$('nav#user-navigation ul li a').bind('click', function(event){
	event.stopPropagation();
});

$('nav#user-navigation ul li a#logout').bind('click', function(event){
	event.preventDefault();
	var lg = new Login();
	lg.logout(function() {
		window.location.replace('/');
	});
});





// --------------------------------------------------------------------------------
// m�todo:			anonymous function
// prop�sito:		recupera informa��es b�sicas sobre o usu�rio logado
// par�metros:		-
// retorna:			void
// afeta:			document.getElementById('userName')
// 					document.getElementById('avatarPath')
// depend�ncias:	jQuery
// eventos:			onUserDetailsChange		disparado ap�s o carregamento das informa��es do usu�rio do banco de dados
// exemplo:			n�o se aplica
// coment�rios:		chamada automaticamente, no momento da declara��o da fun��o an�nima
// --------------------------------------------------------------------------------
var userData = {'fullName':null,'avatarPah':null};

(function(){

	$.ajax({
		type:		'GET',
		url:		'/shared/libraries/php/basicBehavior.php',
		dataType:	'json',
		data:		{'action':'getUserDetails'},
		success:	function(data) {
						
						if (data.control && data.control.authenticated == true) {
							
							if (data.recordset && data.recordset.length > 0) {
								
								userData['fullName']  = data.recordset[0].sFullName;
								userData['avatarPah'] = (data.recordset[0].sAvatarPath != null ? data.recordset[0].sAvatarPath : '/shared/style/images/avatar-default.jpg');
								
								document.getElementById('userName').innerText = userData['fullName'];
								document.getElementById('avatarPath').setAttribute('src',userData['avatarPah']);
								
							}
							
							var evt = document.createEvent('Event');
							evt.initEvent('onUserDetailsChange',true,true);
							document.dispatchEvent(evt);
						}
					}
	});
	
})();





/* ------------------------------------------------------------------------------------------------------
 * SABOIA'S LOGO ANIMATION DURING AJAX REQUESTS
*/

$('#footer').ajaxStart(function(event,request,settings){
	$(this).addClass('animated');
});
$('#footer').ajaxComplete(function(event,request,settings){
	$(this).removeClass('animated');
});