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
	You did not send an identifier with the request,
	and it was not an identifier selection request.
	Please return to the relying party and try again.
	</p>
</div>

{include file='footer' sandbox=false}
</body>
</html>
