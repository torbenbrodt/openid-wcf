<div class="formElement">
	<div class="formField">
		<form method="get" action="{$openid_url}">
			<script type="text/javascript">
			function openid(elem, msg) {
				var x = prompt(msg);
				if(x) {
					elem.href = elem.href.replace(/\\1/, x);
					return true;
				}
	
				return false;
			}
			</script>
	
			Sie können sich mit ihrem existieren Account bestimmter Anbieter bei uns authentifizieren.<br/>
			Das ganze funktioniert über die s.g. OpenID Schnittstelle - es werden keine Zugangsdaten ausgetauscht.<br/>

			<a href="{$openid_url}&identifier=https://www.google.com/accounts/o8/id">Google</a>
			<a href="{$openid_url}&identifier=http://yahoo.com/">Yahoo</a>
			<a href="{$openid_url}&identifier=http://openid.aol.com/\1" onclick="return openid(this)">AOL</a>
			<a href="{$openid_url}&identifier=http://\1.myopenid.com/" onclick="return openid(this)">myOpenID</a>

			<p>... oder geben Sie ihre OpenID manuell ein:<br/>
			<input type="text" name="identifier" class="openid" value="https://www.google.com/accounts/o8/id" /></p>
			<p>
				<input type="submit" value="Weiter &raquo;" />
			</p>
		</form>
	</div>
</div>
