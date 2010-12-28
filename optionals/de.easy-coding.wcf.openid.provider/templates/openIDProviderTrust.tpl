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
	<div class="form">
	  <form method="post" action="%s">
	  %s
	    <input type="submit" name="trust" value="Confirm" />
	    <input type="submit" value="Do not confirm" />
	  </form>
	</div>
	</p>

	<p>
	<p>Do you wish to confirm your identity .
	(<code>%s</code>) with <code>%s</code>?</p>);


	<p>You entered the server URL at the RP.
	Please choose the name you wish to use.  If you enter nothing, the request will be cancelled.<br/>
	<input type="text" name="idSelect" /></p>
	
	</p>
</div>

{include file='footer' sandbox=false}
</body>
</html>
