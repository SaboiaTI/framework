/* ------------------------------------------------------------------------------------------------------
 * basicBehavior.js
 * 
 * comportamentos comuns às páginas básicas do framework bloop
 * @author 			Saboia Tecnologia da Informação <relacionamento@saboia.com.br>
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
// método:			anonymous function
// propósito:		recupera informações básicas sobre o usuário logado
// parâmetros:		-
// retorna:			void
// afeta:			document.getElementById('userName')
// 					document.getElementById('avatarPath')
// dependências:	jQuery
// eventos:			onUserDetailsChange		disparado após o carregamento das informações do usuário do banco de dados
// exemplo:			não se aplica
// comentários:		chamada automaticamente, no momento da declaração da função anônima
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