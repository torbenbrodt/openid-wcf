{include file="documentHeader"}
<head>
	<title>{lang}wcf.openid.provider.about{/lang} - {lang}{PAGE_TITLE}{/lang}</title>
	{include file='headInclude' sandbox=false}
</head>
<body{if $templateName|isset} id="tpl{$templateName|ucfirst}"{/if}>
{include file='header' sandbox=false}

<div id="main">	
	<ul class="breadCrumbs">
		<li><a href="index.php?page=Index{@SID_ARG_2ND}"><img src="{icon}indexS.png{/icon}" alt="" /> <span>{lang}{PAGE_TITLE}{/lang}</span></a> &raquo;</li>
	</ul>
	<div class="mainHeadline">
		<img src="{icon}membersL.png{/icon}" alt="" />
		<div class="headlineContainer">
			<h2> {lang}wcf.user.membersList.title{/lang}</h2>
		</div>
	</div>
	
	{if $userMessages|isset}{@$userMessages}{/if}
	
	<p>
	  This is an <a href="http://www.openid.net/">OpenID</a> server
	  endpoint. This server is built on the <a
	  href="http://github.com/openid/php-openid">JanRain PHP OpenID
	  library</a>. Since OpenID consumer sites will need to directly contact this
	  server, it must be accessible over the Internet (not behind a firewall).
	</p>
	<p>
	  To use this server, you will have to set up a URL to use as an identifier.
	  Insert the following markup into the <code>&lt;head&gt;</code> of the HTML
	  document at that URL:
	</p>
	<pre>&lt;link rel="openid.server" href="%s" /&gt;</pre>
	<p>
	  Then configure this server so that you can log in with that URL.
	</p>
</div>

{include file='footer' sandbox=false}
</body>
</html>
