	</head>
	<body>
		<header class="page-header box">
			<div class="table">
				<div class="cell logo-header"><img src="/shared/style/images/logo-client.png"></div>
				<div class="cell"><h1 class="page-title"><?php print($header{"page_title"});?></h1></div>
				<div class="cell user-info">
					<button id="profile-information" class="button profile panel-button-menu"><img id="avatarPath" src="/shared/style/images/avatar-default.jpg" width="18" height="18">&nbsp;<span id="userName">&nbsp;&nbsp;&mdash;&nbsp;&nbsp;</span></button>
					<a href="/modules/request/list/" id="" class="button menu" title="página inicial"><img src="/shared/style/images/bt-system.png" alt="página inicial"></a>
					<div id="profile-menu" class="box floating-menu" style="position:absolute;top:40px;right:40px;display:none;text-align:left;">
						<ul class="floating-menu-list">
							<li class="floating-menu-item"><a id="" class="" href="http://me.saboia.pro/" target="_blank"><?=m('MSG_PROFILE');?></a></li>
							<li class="floating-menu-item"><a id="button-logout" class="" href="/" ><?=m('MSG_LOGOUT');?></a></li>
							<hr>
							<li class="floating-menu-item"><a id="" href="http://www.saboia.com.br" target="_blank" class="" style="font-size:12px;font-style:italic;">Saboia Tecnologia da Informação</a></li>
						</ul>
					</div>
				</div>
			</div>
		</header>
