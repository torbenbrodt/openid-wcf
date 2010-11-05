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
