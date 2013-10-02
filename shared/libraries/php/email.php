<?php

class Email {

	const ADMIN_MAIL_ADDRESS = 'no-reply@saboia.com.br';
	const REPLY_MAIL_ADDRESS = 'relacionamento@saboia.com.br';
	const LOG_MAIL_ADDRESS	 = 'log.saboia@gmail.com';

	const EOL = "\r\n"; 		// caractere de quebra de linha, PHP_EOL

	public  $type;
	public  $to;
	public  $cc;
	public  $title;
	public  $message;

	private $properties;			// armazenamento das propriedades de sobrecarga;
	private $idSYSTEMUser;			// usuário logado no momento de envio do email;




	public function __construct() {

		if (!isset($_SESSION["idUser"])) return false;

		$this->idSYSTEMUser	= $_SESSION["idUser"];

		$this->type			= null;

		$this->to			= array();
		$this->cc			= array();
		$this->title		= null;
		$this->message		= null;

		$this->properties	= array();
	}






	public function __get ($propertyName) {

		if (array_key_exists($propertyName, $this->properties)) {
			return $this->properties[$propertyName];
		} else {
			return null;
		}
    }






	public function __set ($propertyName, $value) {

		$this->properties[$propertyName] = $value;
		return true;
    }

	public function send() {

		$mailError;
		$to;
		$cc;
		$headers;
		$mailStatus;

		// não pode enviar email sem destinatário, remetente ou conteúdo
		if (
			count($this->to)  === 0 &&
			count($this->cc)  === 0
		) return false;

		if (!is_array($this->to)) $this->to = ( !is_null($this->to) ? array($this->to) : array() );
		if (!is_array($this->cc)) $this->cc = ( !is_null($this->cc) ? array($this->cc) : array() );

		if ($this->title   === null) return false;
		if ($this->message === null) return false;


		// sanitize de endereços de email:
		$mailError = 0;

		if (count($this->to) > 0) { foreach($this->to  as &$data) { $data = filter_var($data, FILTER_VALIDATE_EMAIL); if(!$data) $mailError++; } }
		if (count($this->cc) > 0) { foreach($this->cc  as &$data) { $data = filter_var($data, FILTER_VALIDATE_EMAIL); if(!$data) $mailError++; } }

		if ($mailError > 0) return false;

		if (is_array($this->to))  $to  = implode(', ', $this->to);
		if (is_array($this->cc))  $cc  = implode(', ', $this->cc);



		// sanitize de subject e message, prevenindo header injection:
		$this->title 	= str_replace(array("\r", self::EOL, "%0a", "%0d"), '', stripslashes($this->title));
		$this->message 	= str_replace(array("\r", self::EOL, "%0a", "%0d"), '', stripslashes($this->message));



		// informações do header e envio do email:
		$headers   = array();

		$headers[] = "From: ".self::ADMIN_MAIL_ADDRESS;
		// só envia cópias do email se o sistema não está em modo DEBUG
		if (IS_DEBUG == false) {
			$headers[] = "Cc: ".$cc;
			$headers[] = "Bcc: ".self::LOG_MAIL_ADDRESS;
		}
		$headers[] = "Reply-To: ".self::REPLY_MAIL_ADDRESS;
		$headers[] = "Return-Path: ".self::REPLY_MAIL_ADDRESS;
		$headers[] = "Subject: ".$this->title;

		$headers[] = "MIME-Version: 1.0";
		$headers[] = "Content-type: text/html; charset=iso-8859-1";

		$headers[] = "X-Sender: ".self::ADMIN_MAIL_ADDRESS;
		$headers[] = "X-Priority: 6";
		$headers[] = "X-Mailer: PHP/".phpversion();

		$headers = implode(self::EOL, $headers);

		if (IS_DEBUG == true) {
			// se o sistema está em modo DEBUG envia email para a saboia
			$mailStatus = mail('rafael@saboia.com.br', $this->title, $this->message, $headers);
		} else {
			// email para os destinatários reais
			$mailStatus = mail($to, $this->title, $this->message, $headers);
		}

		return $mailStatus;

	}






	public function buildMessage() {

		if ($this->type === null) return null;
		$this->type = strtoupper($this->type);


		switch($this->type) {

			case 'OPPORTUNITY' :

				if( !array_key_exists('opportunityStatus', $this->properties) ) return null;

				$messageParams = array();

				$messageParams['opportunityStatus']		= array_key_exists('opportunityStatus'	, $this->properties) ? $this->properties['opportunityStatus']	: '';
				$messageParams['opportunityId'] 		= array_key_exists('opportunityId'		, $this->properties) ? $this->properties['opportunityId']		: '';
				$messageParams['opportunityValue'] 		= array_key_exists('opportunityValue'	, $this->properties) ? $this->properties['opportunityValue']	: '';
				$messageParams['channelDisplayName'] 	= array_key_exists('channelDisplayName'	, $this->properties) ? $this->properties['channelDisplayName']	: '';
				$messageParams['channelContactName'] 	= array_key_exists('channelContactName'	, $this->properties) ? $this->properties['channelContactName']	: '';
				$messageParams['channelContactEmail'] 	= array_key_exists('channelContactEmail', $this->properties) ? $this->properties['channelContactEmail']	: '';
				$messageParams['clientDisplayName'] 	= array_key_exists('clientDisplayName'	, $this->properties) ? $this->properties['clientDisplayName']	: '';
				$messageParams['clientContactName'] 	= array_key_exists('clientContactName'	, $this->properties) ? $this->properties['clientContactName']	: '';
				$messageParams['clientContactEmail'] 	= array_key_exists('clientContactEmail'	, $this->properties) ? $this->properties['clientContactEmail']	: '';
				$messageParams['clientContactPhone'] 	= array_key_exists('clientContactPhone'	, $this->properties) ? $this->properties['clientContactPhone']	: '';


				switch($this->properties['opportunityStatus']) {

					case 2 :

						// pendente
						$this->title 	= 'OPORTUNIDADE PENDENTE - #'.$messageParams['opportunityId'];

						$messageParams['statusImage'] = 'barra_pendente.jpg';
						$messageParams['statusColor'] = '#FFCC00';
						$messageParams['statusLabel'] = 'PENDENTE';

						$messageParams['messageText']  = '<p><strong>Você possui uma nova oportunidade para ser aprovada.</strong><br>';
						$messageParams['messageText'] .= 'Para mais informações, acesse <a href="http://'.$_SERVER['SERVER_NAME'].'/" target="_blank">'.$_SERVER['SERVER_NAME'].'</a></p>';

					break;

					case 4 :
						// rejeitada
						$this->title 	= 'OPORTUNIDADE REJEITADA - #'.$messageParams['opportunityId'];

						$messageParams['statusImage'] = 'barra_rejeitada.jpg';
						$messageParams['statusColor'] = '#FF3300';
						$messageParams['statusLabel'] = 'REJEITADA';

						$messageParams['messageText']  = '<p><strong>Sua oportunidade foi rejeitada.</strong><br>';
						$messageParams['messageText'] .= 'Para mais informações, acesse <a href="http://'.$_SERVER['SERVER_NAME'].'/" target="_blank">'.$_SERVER['SERVER_NAME'].'</a></p>';

					break;

					case 5 :
						// aprovada
						$this->title 	= 'OPORTUNIDADE APROVADA - #'.$messageParams['opportunityId'];

						$messageParams['statusImage'] = 'barra_aprovado.jpg';
						$messageParams['statusColor'] = '#00CC33';
						$messageParams['statusLabel'] = 'APROVADA';

						$messageParams['messageText']  = '<p><strong>Sua oportunidade foi aprovada.</strong><br>';
						$messageParams['messageText'] .= 'Para mais informações, acesse <a href="http://'.$_SERVER['SERVER_NAME'].'/" target="_blank">'.$_SERVER['SERVER_NAME'].'</a></p>';

					break;

					case 7 :
						// desistência do canal
						$this->title 	= 'OPORTUNIDADE PERDIDA POR DESISTÊNCIA - #'.$messageParams['opportunityId'];

						$messageParams['statusImage'] = 'barra_expirada.jpg';
						$messageParams['statusColor'] = '#D9D9AD';
						$messageParams['statusLabel'] = 'DESISTÊNCIA DO CANAL';

						$messageParams['messageText']  = '<p><strong>Sua oportunidade foi perdida por desistência.</strong><br>';
						$messageParams['messageText'] .= 'Para mais informações, acesse <a href="http://'.$_SERVER['SERVER_NAME'].'/" target="_blank">'.$_SERVER['SERVER_NAME'].'</a></p>';

					break;

					case 8 :
						// expirada
						$this->title 	= 'OPORTUNIDADE EXPIRADA - #'.$messageParams['opportunityId'];

						$messageParams['statusImage'] = 'barra_expirada.jpg';
						$messageParams['statusColor'] = '#E5E5B8';
						$messageParams['statusLabel'] = 'EXPIRADA';

						$messageParams['messageText']  = '<p><strong>Sua oportunidade expirou.</strong><br>';
						$messageParams['messageText'] .= 'Para mais informações, acesse <a href="http://'.$_SERVER['SERVER_NAME'].'/" target="_blank">'.$_SERVER['SERVER_NAME'].'</a></p>';

					break;

					case 9 :
						// desistência do gestor
						$this->title 	= 'OPORTUNIDADE PERDIDA POR DESISTÊNCIA - #'.$messageParams['opportunityId'];

						$messageParams['statusImage'] = 'barra_expirada.jpg';
						$messageParams['statusColor'] = '#E6E6CF';
						$messageParams['statusLabel'] = 'DESISTÊNCIA DO GESTOR';

						$messageParams['messageText']  = '<p><strong>Sua oportunidade foi perdida por desistência do gestor.</strong><br>';
						$messageParams['messageText'] .= 'Para mais informações, acesse <a href="http://'.$_SERVER['SERVER_NAME'].'/" target="_blank">'.$_SERVER['SERVER_NAME'].'</a></p>';

					break;

					case 10 :
						// perdida
						$this->title 	= 'OPORTUNIDADE PERDIDA - #'.$messageParams['opportunityId'];

						$messageParams['statusImage'] = 'barra_expirada.jpg';
						$messageParams['statusColor'] = '#F2F2DF';
						$messageParams['statusLabel'] = 'PERDIDA';

						$messageParams['messageText']  = '<p><strong>Sua oportunidade foi perdida.</strong><br>';
						$messageParams['messageText'] .= 'Para mais informações, acesse <a href="http://'.$_SERVER['SERVER_NAME'].'/" target="_blank">'.$_SERVER['SERVER_NAME'].'</a></p>';

					break;

					case 11 :
						// ganha
						$this->title 	= 'OPORTUNIDADE GANHA - #'.$messageParams['opportunityId'];

						$messageParams['statusImage'] = 'barra_aprovado.jpg';
						$messageParams['statusColor'] = '#66FF99';
						$messageParams['statusLabel'] = 'GANHA';

						$messageParams['messageText']  = '<p><strong>Sua oportunidade foi ganha.</strong><br>';
						$messageParams['messageText'] .= 'Para mais informações, acesse <a href="http://'.$_SERVER['SERVER_NAME'].'/" target="_blank">'.$_SERVER['SERVER_NAME'].'</a></p>';

					break;

					default :
						$this->title 	= 'OPORTUNIDADE REGISTRADA - #'.$messageParams['opportunityId'];

						$messageParams['statusImage'] = 'barra_registrada.jpg';
						$messageParams['statusColor'] = '#CCFFFF';
						$messageParams['statusLabel'] = 'RASCUNHO';

						$messageParams['messageText']  = '<p><strong>Sua oportunidade foi registrada.</strong><br>';
						$messageParams['messageText'] .= 'Para mais informações, acesse <a href="http://'.$_SERVER['SERVER_NAME'].'/" target="_blank">'.$_SERVER['SERVER_NAME'].'</a></p>';

					break;

				}


				// termina de preencher as informações do corpo do email:
				if ( !array_key_exists('messageText', $messageParams) ) return null;

				$messageParams['messageText'] .= '<p><strong>Parceiro</strong>: '.$this->properties['channelDisplayName'].'<br><strong>Contato</strong>: '.$this->properties['channelContactName'].' / '.$this->properties['channelContactEmail'].'</p>'.self::EOL;
				$messageParams['messageText'] .= '<p><strong>Cliente</strong>: '.$this->properties['clientDisplayName'].'<br><strong>Contato</strong>: '.$this->properties['clientContactName'].' / '.$this->properties['clientContactEmail'].' / '.$this->properties['clientContactPhone'].'</p>'.self::EOL;

				$messageParams['messageText'] .= '<table cellspacing="5" style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#333333;width:100%;margin:20px 0 50px 0;padding:0;">'.self::EOL;
				$messageParams['messageText'] .= 	'<tr>'.self::EOL;
				$messageParams['messageText'] .= 		'<td style="width:33%;padding:2px 10px;margin:0;"><strong>Oportunidade nº</strong></td>'.self::EOL;
				$messageParams['messageText'] .= 		'<td style="width:33%;padding:2px 10px;margin:0;"><strong>Status</strong></td>'.self::EOL;
				$messageParams['messageText'] .= 		'<td style="width:33%;padding:2px 10px;margin:0;text-align:right;"><strong>Valor total</strong></td>'.self::EOL;
				$messageParams['messageText'] .= 	'</tr>'.self::EOL;
				$messageParams['messageText'] .= 	'<tr style="color:#543C8F;background-color:#EDEDED;">'.self::EOL;
				$messageParams['messageText'] .= 		'<td style="padding:2px 10px;margin:0;font-size:32px;text-align:center;">';
				$messageParams['messageText'] .= 			'<a href="http://'.$_SERVER['SERVER_NAME'].'/modules/opportunity/followup/?id='.$this->properties['opportunityId'].'" target="_blank" style="text-decoration:none;"><strong>'.$messageParams['opportunityId'].'</strong></a>';
				$messageParams['messageText'] .= 		'</td>'.self::EOL;
				$messageParams['messageText'] .= 		'<td style="padding:2px 10px;margin:0;background-color:#'.$messageParams['statusColor'].';text-align:center;">';
				$messageParams['messageText'] .= 			'<strong>'.$messageParams['statusLabel'].'</strong>';
				$messageParams['messageText'] .= 		'</td>'.self::EOL;
				$messageParams['messageText'] .= 		'<td style="padding:2px 10px;margin:0;text-align:right;">';
				$messageParams['messageText'] .= 			'<strong>R$ '.floatToMoney($this->properties['opportunityValue']).'</strong>';
				$messageParams['messageText'] .= 		'</td>'.self::EOL;
				$messageParams['messageText'] .= 	'</tr>'.self::EOL;
				$messageParams['messageText'] .= '</table>'.self::EOL;

				return $this->buildHTMLMessage($messageParams);

			break;



			case 'EXPIRING_OPPORTUNITY' :

				if ( !array_key_exists('opportunityStatus', $this->properties) ) return null;

				$query = 'SELECT iDiasAtePrimeiroAviso, iDiasAteExpiracao FROM tbADMINParametersOfOpportunity WHERE id=1;';
				$result = mysql_query(utf8_decode($query));
				if ($result) {
					if (mysql_num_rows($result) == 0) { return null; }
					while ($row = mysql_fetch_assoc($result)) {
						$iDiasAtePrimeiroAviso = $row['iDiasAtePrimeiroAviso'];
						$iDiasAteExpiracao = $row['iDiasAteExpiracao'];
					}
				}

				$daysWithoutUpdate 	= intval($iDiasAtePrimeiroAviso,10) + intval($iDiasAteExpiracao,10);
				$expirationDate		= mktime(0,0,0,date("m"), date("d")+$iDiasAteExpiracao, date("Y"));
				$expirationDate		= date("d\/m\/Y", $expirationDate);

				$messageParams = array();

				$messageParams['opportunityStatus']		= array_key_exists('opportunityStatus'	, $this->properties) ? $this->properties['opportunityStatus']	: '';
				$messageParams['opportunityId'] 		= array_key_exists('opportunityId'		, $this->properties) ? $this->properties['opportunityId']		: '';
				$messageParams['opportunityValue'] 		= array_key_exists('opportunityValue'	, $this->properties) ? $this->properties['opportunityValue']	: '';
				$messageParams['channelDisplayName'] 	= array_key_exists('channelDisplayName'	, $this->properties) ? $this->properties['channelDisplayName']	: '';
				$messageParams['channelContactName'] 	= array_key_exists('channelContactName'	, $this->properties) ? $this->properties['channelContactName']	: '';
				$messageParams['channelContactEmail'] 	= array_key_exists('channelContactEmail', $this->properties) ? $this->properties['channelContactEmail']	: '';
				$messageParams['clientDisplayName'] 	= array_key_exists('clientDisplayName'	, $this->properties) ? $this->properties['clientDisplayName']	: '';
				$messageParams['clientContactName'] 	= array_key_exists('clientContactName'	, $this->properties) ? $this->properties['clientContactName']	: '';
				$messageParams['clientContactEmail'] 	= array_key_exists('clientContactEmail'	, $this->properties) ? $this->properties['clientContactEmail']	: '';
				$messageParams['clientContactPhone'] 	= array_key_exists('clientContactPhone'	, $this->properties) ? $this->properties['clientContactPhone']	: '';

				$this->title 	= 'SUA OPORTUNIDADE IRÁ EXPIRAR';

				switch($this->properties['opportunityStatus']) {

					case 2 : 	$messageParams['statusLabel'] = 'PENDENTE'; 			 break;
					case 4 : 	$messageParams['statusLabel'] = 'REJEITADA'; 			 break;
					case 5 : 	$messageParams['statusLabel'] = 'APROVADA'; 			 break;
					case 7 : 	$messageParams['statusLabel'] = 'DESISTÊNCIA DO CANAL';  break;
					case 8 : 	$messageParams['statusLabel'] = 'EXPIRADA'; 			 break;
					case 9 : 	$messageParams['statusLabel'] = 'DESISTÊNCIA DO GESTOR'; break;
					case 10 : 	$messageParams['statusLabel'] = 'PERDIDA'; 				 break;
					case 11 : 	$messageParams['statusLabel'] = 'GANHA'; 				 break;
					default : 	$messageParams['statusLabel'] = 'RASCUNHO'; 			 break;
				}

				$messageParams['statusImage'] = 'barra_expirada.jpg';
				$messageParams['statusColor'] = '#E5E5B8';

				$messageParams['messageText']  = '<p><strong>Sua oportunidade irá expirar em '.$expirationDate.'.</strong><br>';
				$messageParams['messageText'] .= 'Oportunidades sem acompanhamento por um prazo superior a '.$daysWithoutUpdate.' dias são expiradas automaticamente pelo sistema.</p>'.self::EOL;
				$messageParams['messageText'] .= '<p>Para mantê-la ativa, acesse <a href="http://'.$_SERVER['SERVER_NAME'].'/modules/opportunity/followup/?id='.$this->properties['opportunityId'].'" target="_blank">'.$_SERVER['SERVER_NAME'].'</a></p>';

				$messageParams['messageText'] .= '<p><strong>Parceiro</strong>: '.$this->properties['channelDisplayName'].'<br><strong>Contato</strong>: '.$this->properties['channelContactName'].' / '.$this->properties['channelContactEmail'].'</p>'.self::EOL;
				$messageParams['messageText'] .= '<p><strong>Cliente</strong>: '.$this->properties['clientDisplayName'].'<br><strong>Contato</strong>: '.$this->properties['clientContactName'].' / '.$this->properties['clientContactEmail'].' / '.$this->properties['clientContactPhone'].'</p>'.self::EOL;

				$messageParams['messageText'] .= '<table cellspacing="5" style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#333333;width:100%;margin:20px 0 50px 0;padding:0;">'.self::EOL;
				$messageParams['messageText'] .= 	'<tr>'.self::EOL;
				$messageParams['messageText'] .= 		'<td style="width:33%;padding:2px 10px;margin:0;"><strong>Oportunidade nº</strong></td>'.self::EOL;
				$messageParams['messageText'] .= 		'<td style="width:33%;padding:2px 10px;margin:0;"><strong>Status</strong></td>'.self::EOL;
				$messageParams['messageText'] .= 		'<td style="width:33%;padding:2px 10px;margin:0;text-align:right;"><strong>Valor total</strong></td>'.self::EOL;
				$messageParams['messageText'] .= 	'</tr>'.self::EOL;
				$messageParams['messageText'] .= 	'<tr style="color:#543C8F;background-color:#EDEDED;">'.self::EOL;
				$messageParams['messageText'] .= 		'<td style="padding:2px 10px;margin:0;font-size:32px;text-align:center;">';
				$messageParams['messageText'] .= 			'<a href="http://'.$_SERVER['SERVER_NAME'].'/modules/opportunity/followup/?id='.$this->properties['opportunityId'].'" target="_blank" style="text-decoration:none;"><strong>'.$messageParams['opportunityId'].'</strong></a>';
				$messageParams['messageText'] .= 		'</td>'.self::EOL;
				$messageParams['messageText'] .= 		'<td style="padding:2px 10px;margin:0;background-color:#'.$messageParams['statusColor'].';text-align:center;">';
				$messageParams['messageText'] .= 			'<strong>'.$messageParams['statusLabel'].'</strong>';
				$messageParams['messageText'] .= 		'</td>'.self::EOL;
				$messageParams['messageText'] .= 		'<td style="padding:2px 10px;margin:0;text-align:right;">';
				$messageParams['messageText'] .= 			'<strong>R$ '.floatToMoney($this->properties['opportunityValue']).'</strong>';
				$messageParams['messageText'] .= 		'</td>'.self::EOL;
				$messageParams['messageText'] .= 	'</tr>'.self::EOL;
				$messageParams['messageText'] .= '</table>'.self::EOL;

				return $this->buildHTMLMessage($messageParams);

			break;


			case 'NEW_CHANNEL' :

				if( !array_key_exists('sFullName', 		$this->properties) ) return null;
				if( !array_key_exists('sDisplayName', 	$this->properties) ) return null;
				if( !array_key_exists('sState', 		$this->properties) ) return null;
				if( !array_key_exists('sCity', 			$this->properties) ) return null;
				if( !array_key_exists('sNeighborhood', 	$this->properties) ) return null;
				if( !array_key_exists('sAddress', 		$this->properties) ) return null;
				if( !array_key_exists('sSite', 			$this->properties) ) return null;
				if( !array_key_exists('sName', 			$this->properties) ) return null;
				if( !array_key_exists('sEmail', 		$this->properties) ) return null;
				if( !array_key_exists('sFirstPhone', 	$this->properties) ) return null;
				if( !array_key_exists('sSecondPhone', 	$this->properties) ) return null;

				$messageParams = array();

				$this->title = 'NOVO CANAL';

				$messageParams['statusImage'] = 'barra_default.jpg';
				$messageParams['statusColor'] = '#E5E5B8';

				$messageParams['messageText']  = '<p>O canal <strong>'. $this->properties['sFullName'] .'</strong> entrou em contato, desejando se tornar um canal de vendas.<br>'.self::EOL;
				$messageParams['messageText'] .= 'Para para visualizar o cadastro deste canal, acesse: <a href="http://'.$_SERVER['SERVER_NAME'].'/modules/opportunity/channel/" target="_blank">'.$_SERVER['SERVER_NAME'].'/modules/opportunity/channel</a></p>'.self::EOL;
				$messageParams['messageText'] .= '<p>Seguem abaixo os dados de cadastro fornecidos pelo canal.</p>'.self::EOL;

				$messageParams['messageText'] .= '<p><strong>Razão Social:</strong> '.	$this->properties['sFullName'].'</p>'.self::EOL;
				$messageParams['messageText'] .= '<p><strong>Nome Fantasia:</strong> '.	$this->properties['sDisplayName'].'</p>'.self::EOL;
				$messageParams['messageText'] .= '<p><strong>Estado:</strong> '.		$this->properties['sState'].'</p>'.self::EOL;
				$messageParams['messageText'] .= '<p><strong>Cidade:</strong> '.		$this->properties['sCity'].'</p>'.self::EOL;
				$messageParams['messageText'] .= '<p><strong>Bairro:</strong> '.		$this->properties['sNeighborhood'].'</p>'.self::EOL;
				$messageParams['messageText'] .= '<p><strong>Endereço:</strong> '.		$this->properties['sAddress'].'</p>'.self::EOL;
				$messageParams['messageText'] .= '<p><strong>Site:</strong> '.			$this->properties['sSite'].'</p>'.self::EOL;
				$messageParams['messageText'] .= '<p><strong>Nome:</strong> '.			$this->properties['sName'].'</p>'.self::EOL;
				$messageParams['messageText'] .= '<p><strong>Email:</strong> '.			$this->properties['sEmail'].'</p>'.self::EOL;
				$messageParams['messageText'] .= '<p><strong>Telefone 1:</strong> '.	$this->properties['sFirstPhone'].'</p>'.self::EOL;
				$messageParams['messageText'] .= '<p><strong>Telefons 2:</strong> '.	$this->properties['sSecondPhone'].'</p>'.self::EOL;

				return $this->buildHTMLMessage($messageParams);

			break;


			case 'NEW_USER' :

				// idêntico ao NEW_PASSWORD;
				// TO-DO: diferenciar os layouts dos emails NEW_USER e NEW_PASSWORD

				if( !array_key_exists('displayName', $this->properties) ) return null;
				if( !array_key_exists('username', 	 $this->properties) ) return null;
				if( !array_key_exists('password', 	 $this->properties) ) return null;

				$messageParams = array();

				$this->title = 'ACESSO AO SISTEMA SABOIA CANAL';

				$messageParams['statusImage'] = 'barra_default.jpg';
				$messageParams['statusColor'] = '#E5E5B8';

				$messageParams['messageText']  = '<p>Caro(a) <strong>'.$this->properties['displayName'].'</strong>,<br><br>Você solicitou através de nosso sistema a sua senha de acesso ao Saboia Canal.<br>Seguem abaixo os seus dados de acesso:</p>'.self::EOL;
				$messageParams['messageText'] .= '<pre><strong>usuário:</strong>'.$this->properties['username'].''.self::EOL;
				$messageParams['messageText'] .= '<strong>senha:</strong>'.$this->properties['password'].'</pre>'.self::EOL;
				$messageParams['messageText'] .= '<p>Acesse o endereço <a href="http://'.$_SERVER['SERVER_NAME'].'/" target="_blank">'.$_SERVER['SERVER_NAME'].'</a></p>'.self::EOL;
				$messageParams['messageText'] .= '<p>Após acessar o sistema, aconselhamos a você trocar sua senha* periodicamente, por segurança.</p>'.self::EOL;
				$messageParams['messageText'] .= '<p>*O sistema de senhas obedece as seguintes regras:</p>'.self::EOL;
				$messageParams['messageText'] .= '<ul style="margin:0;padding:0 0 0 20px;">'.self::EOL;
				$messageParams['messageText'] .= 	'<li>A senha deve ter no mínimo 6 caracteres;</li>'.self::EOL;
				$messageParams['messageText'] .= 	'<li>A senha deve ter no mínimo um caractere de três conjuntos a seguir:</li>'.self::EOL;
				$messageParams['messageText'] .= 	'<ul style="margin:0;padding:0 0 0 10px;">'.self::EOL;
				$messageParams['messageText'] .= 		'<li>Vogais minúsculas (aeiou);</li>'.self::EOL;
				$messageParams['messageText'] .= 		'<li>Vogais maiúsculas (AEIOU);</li>'.self::EOL;
				$messageParams['messageText'] .= 		'<li>Consoantes minúsculas (bcdfghjklmnpqrstvwxyz);</li>'.self::EOL;
				$messageParams['messageText'] .= 		'<li>Consoantes maiúsculas (BCDFGHJKLMNPQRSTVWXYZ);</li>'.self::EOL;
				$messageParams['messageText'] .= 		'<li>Números (1234567890);</li>'.self::EOL;
				$messageParams['messageText'] .= 		'<li>Símbolos (!@#$%&*?-);</li>'.self::EOL;
				$messageParams['messageText'] .= 	'</ul>'.self::EOL;
				$messageParams['messageText'] .= '</ul>'.self::EOL;
			//	$messageParams['messageText'] .= '<p>Veja um exemplo de senha segura: dqoo58XF-AA</p>';

				return $this->buildHTMLMessage($messageParams);

			break;


			case 'NEW_PASSWORD' :

				// idêntico ao NEW_USER;
				// TO-DO: diferenciar os layouts dos emails NEW_USER e NEW_PASSWORD

				if( !array_key_exists('displayName', $this->properties) ) return null;
				if( !array_key_exists('username', 	 $this->properties) ) return null;
				if( !array_key_exists('password', 	 $this->properties) ) return null;

				$messageParams = array();

				$this->title = 'ACESSO AO SISTEMA SABOIA CANAL';

				$messageParams['statusImage'] = 'barra_default.jpg';
				$messageParams['statusColor'] = '#E5E5B8';

				$messageParams['messageText']  = '<p>Caro(a) <strong>'.$this->properties['displayName'].'</strong>,<br><br>Você solicitou através de nosso sistema a sua senha de acesso ao Saboia Canal.<br>Seguem abaixo os seus dados de acesso:</p>'.self::EOL;
				$messageParams['messageText'] .= '<pre><strong>usuário:</strong>'.$this->properties['username'].''.self::EOL;
				$messageParams['messageText'] .= '<strong>senha:</strong>'.$this->properties['password'].'</pre>'.self::EOL;
				$messageParams['messageText'] .= '<p>Acesse o endereço <a href="http://'.$_SERVER['SERVER_NAME'].'/" target="_blank">'.$_SERVER['SERVER_NAME'].'</a></p>'.self::EOL;
				$messageParams['messageText'] .= '<p>Após acessar o sistema, aconselhamos a você trocar sua senha* periodicamente, por segurança.</p>'.self::EOL;
				$messageParams['messageText'] .= '<p>*O sistema de senhas obedece as seguintes regras:</p>'.self::EOL;
				$messageParams['messageText'] .= '<ul style="margin:0;padding:0 0 0 20px;">'.self::EOL;
				$messageParams['messageText'] .= 	'<li>A senha deve ter no mínimo 6 caracteres;</li>'.self::EOL;
				$messageParams['messageText'] .= 	'<li>A senha deve ter no mínimo um caractere de três conjuntos a seguir:</li>'.self::EOL;
				$messageParams['messageText'] .= 	'<ul style="margin:0;padding:0 0 0 10px;">'.self::EOL;
				$messageParams['messageText'] .= 		'<li>Vogais minúsculas (aeiou);</li>'.self::EOL;
				$messageParams['messageText'] .= 		'<li>Vogais maiúsculas (AEIOU);</li>'.self::EOL;
				$messageParams['messageText'] .= 		'<li>Consoantes minúsculas (bcdfghjklmnpqrstvwxyz);</li>'.self::EOL;
				$messageParams['messageText'] .= 		'<li>Consoantes maiúsculas (BCDFGHJKLMNPQRSTVWXYZ);</li>'.self::EOL;
				$messageParams['messageText'] .= 		'<li>Números (1234567890);</li>'.self::EOL;
				$messageParams['messageText'] .= 		'<li>Símbolos (!@#$%&*?-);</li>'.self::EOL;
				$messageParams['messageText'] .= 	'</ul>'.self::EOL;
				$messageParams['messageText'] .= '</ul>'.self::EOL;
			//	$messageParams['messageText'] .= '<p>Veja um exemplo de senha segura: dqoo58XF-AA</p>';

				return $this->buildHTMLMessage($messageParams);

			break;


			case 'ADMINISTRATION' :

			break;

		}
	}






	private function buildHTMLMessage($messageParams) {

		if ( !$messageParams ) return null;
		if ( !is_array($messageParams) ) return null;



		$mailHead	 = '';
		$mailBody	 = '';
		$mailFooter	 = '';
		$mailMessage = '';



		$mailHead .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.self::EOL;
		$mailHead .= '<html>'.self::EOL;
		$mailHead .= '<head>'.self::EOL;
		$mailHead .= '<title>'.$this->title.'</title>'.self::EOL;
		$mailHead .= '</head>'.self::EOL;
		$mailHead .= '<body style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color#333333;">'.self::EOL;
		$mailHead .= 	'<table style="width:600px;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#333333;background-color:#EDEDED;" cellpadding="0" cellspacing="0">'.self::EOL;
		$mailHead .= 		'<thead>'.self::EOL;
		$mailHead .= 			'<tr style="height:80px;padding-top:30px">'.self::EOL;
		$mailHead .=				'<th style="width:140px;padding-left:20px">'.self::EOL;
		$mailHead .=					'<a href="http://'.$_SERVER['SERVER_NAME'].'/" target="_blank"><img src="http://'.$_SERVER['SERVER_NAME'].'/shared/style/images/logo-client.png" height="50" border="0" align="left" /></a>'.self::EOL;
		$mailHead .=				'</th>'.self::EOL;
		$mailHead .=				'<th style="width:400px;padding-right:20px">'.self::EOL;
		$mailHead .=					'<h3 style="color:#543C8F" align="right">'.$this->title.'</h3>'.self::EOL;
		$mailHead .=				'</th>'.self::EOL;
		$mailHead .=			'</tr>'.self::EOL;
		$mailHead .=			'<tr>'.self::EOL;
		$mailHead .=				'<th colspan="2" style="background-color:'.$messageParams['statusColor'].';">'.self::EOL;
		$mailHead .=					'<img src="http://'.$_SERVER['SERVER_NAME'].'/shared/style/images/email/'.$messageParams['statusImage'].'" width="600" height="36" />'.self::EOL;
		$mailHead .=				'</th>'.self::EOL;
		$mailHead .=			'</tr>'.self::EOL;
		$mailHead .=		'</thead>'.self::EOL;



		$mailBody .=		'<tbody>'.self::EOL;

		$mailBody .=			'<tr style="background-color:#F7F6F1">'.self::EOL;
		$mailBody .=				'<td colspan="2" style="padding:10px 40px 10px 40px">'.self::EOL;
		$mailBody .=					$messageParams['messageText'].self::EOL;
		$mailBody .=				'</td>'.self::EOL;
		$mailBody .=			'</tr>'.self::EOL;

		if ( array_key_exists('customMessage', $this->properties) && !is_null($this->properties['customMessage']) && $this->properties['customMessage'] != '' ) {
			$mailBody .=		'<tr style="background-color:#F7F6F1">'.self::EOL;
			$mailBody .=			'<td colspan="2" style="padding:10px 40px 10px 40px">'.self::EOL;
			$mailBody .=				$this->properties['customMessage'].self::EOL;
			$mailBody .=			'</td>'.self::EOL;
			$mailBody .=		'</tr>'.self::EOL;
		}

		$mailBody .=		'</tbody>'.self::EOL;



		$mailFooter .=		'<tfoot>'.self::EOL;
		$mailFooter .=			'<tr>'.self::EOL;
		$mailFooter .=				'<td colspan="3">'.self::EOL;
		$mailFooter .=					'<img src="http://'.$_SERVER['SERVER_NAME'].'/shared/style/images/email/ropade.jpg" width="600" height="24" />'.self::EOL;
		$mailFooter .=				'</td>'.self::EOL;
		$mailFooter .=			'</tr>'.self::EOL;
		$mailFooter .=			'<tr style="height:70px;">'.self::EOL;
		$mailFooter .=				'<td colspan="3" style="padding:43px 8px 0px">'.self::EOL;
		$mailFooter .=					'<a href="http://www.saboia.com.br" target="_blank"><img src="http://'.$_SERVER['SERVER_NAME'].'/shared/style/images/email/rodape_saboia.jpg" width="56" height="27" align="right" border="0" /></a>'.self::EOL;
		$mailFooter .=				'</td>'.self::EOL;
		$mailFooter .=			'</tr>'.self::EOL;
		$mailFooter .=		'</tfoot>'.self::EOL;
		$mailFooter .=	'</table>'.self::EOL;
		$mailFooter .= '</body>'.self::EOL;
		$mailFooter .= '</html>'.self::EOL;



		$mailMessage 	= $mailHead.$mailBody.$mailFooter;
		$this->message 	= $mailMessage;

		return $mailMessage;

	}




}
