<script type="text/javascript">
function openid(elem, msg) {
	msg = msg || "{lang}wcf.openid.login{/lang}";
	var x = prompt(msg);
	if(x) {
		elem.href = elem.href.replace(/\\1/, x);
		return true;
	}

	return false;
}
</script>
<div class="formElement">
	<div class="formField">
		<fieldset>
			<legend><img src="{icon}openidS.png{/icon}" alt="" /> {lang}wcf.openid.login{/lang}</legend>
{*
			<form method="get" action="{$openid_url}">
*}
			{lang}wcf.openid.login.description{/lang}

			<a href="{$openid_url}&amp;identifier=https://www.google.com/accounts/o8/id">Google</a>
			<a href="{$openid_url}&amp;identifier=http://yahoo.com/">Yahoo</a>
			<a href="{$openid_url}&amp;identifier=http://openid.aol.com/\\1" onclick="return openid(this)">AOL</a>
			<a href="{$openid_url}&amp;identifier=http://www.flickr.com/">Flickr</a>
			<a href="{$openid_url}&amp;identifier=http://\\1.myopenid.com/" onclick="return openid(this)">myOpenID</a>
			<a href="{$openid_url}&amp;identifier=http://technorati.com/people/technorati/\\1" onclick="return openid(this)">Technorati</a>
			<a href="{$openid_url}&amp;identifier=http://\\1.wordpress.com/" onclick="return openid(this)">Wordpress</a>
			<a href="{$openid_url}&amp;identifier=http://\\1.blogspot.com/" onclick="return openid(this)">Blogspot</a>
{*
				<p>... oder geben Sie ihre OpenID manuell ein:<br/>
				<input type="text" name="identifier" class="openid" value="https://www.google.com/accounts/o8/id" /></p>
				<p>
					<input type="submit" value="Weiter &raquo;" />
				</p>
			</form>
*}
		</fieldset>
	</div>
</div>
