{include file="documentHeader"}
<head>
	<link rel="openid2.provider openid.server" href="%s"/>
	<meta http-equiv="X-XRDS-Location" content="%s" />
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
	  This is the identity page for users of this server.
	</p>
</div>

{include file='footer' sandbox=false}
</body>
</html>
