<!DOCTYPE html>
<html lang="pt-br">
	<head>
		<meta charset="utf-8">
		<title><?php print( $header{"page_title"} . $header{"app_title"} )?></title>
		<meta name="description" content="Software de gerenciamento de canal desenvolvido pela Saboia Tecnologia da Informação">
		<meta name="author" content="saboia.com">
		<meta name="generator" content="BLOOP! bl-oop.org --beluga-- v.2.0">
		<meta name="HandheldFriendly" content="True">
		<meta name="MobileOptimized" content="320">
		<meta name="viewport" content="width=device-width">
		<meta http-equiv="cleartype" content="on">
		<link rel="apple-touch-icon-precomposed" sizes="144x144" href="shared/images/apple-touch-icon-144x144-precomposed.png">
		<link rel="apple-touch-icon-precomposed" sizes="72x72" href="/shared/images/apple-touch-icon-72x72-precomposed.png">
		<link rel="apple-touch-icon-precomposed" href="/shared/images/apple-touch-icon-57x57-precomposed.png">
		<link rel="shortcut icon" href="/shared/style/images/favicon.ico">
		<!-- This script prevents links from opening in Mobile Safari. https://gist.github.com/1042026 -->
		<!--
		<script>(function(a,b,c){if(c in b&&b[c]){var d,e=a.location,f=/^(a|html)$/i;a.addEventListener("click",function(a){d=a.target;while(!f.test(d.nodeName))d=d.parentNode;"href"in d&&(d.href.indexOf("http")||~d.href.indexOf(e.host))&&(a.preventDefault(),e.href=d.href)},!1)}})(document,window.navigator,"standalone")</script>
		-->
		<link rel="stylesheet" href="/shared/style/html-min.css">
		<link rel="stylesheet" href="/shared/style/global.css">
		<link rel="stylesheet" href="/shared/style/structure.css">
		<link rel="stylesheet" href="/shared/style/smoothness/jquery-ui-1.10.2.custom.css">
		<script type="text/javascript">var DICTIONARY = <?php echo json_encode($DICTIONARY); ?>;</script> 
