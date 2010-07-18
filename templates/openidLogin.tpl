<div class="formElement">
	<div class="formField">
		<div id="fb-root"></div>
		<script type="text/javascript">
		window.fbAsyncInit = function() {
			FB.init({
				appId   : '{FACEBOOK_APPID}',
				session : {$session|json_encode}, // don't refetch the session when PHP already has it
				status  : true, // check login status
				cookie  : true, // enable cookies to allow the server to access the session
				xfbml   : true // parse XFBML
			});

			// whenever the user logs in, we refresh the page
			FB.Event.subscribe('auth.login', function() {
				window.location.reload();
			});
		};

		(function() {
			var e = document.createElement('script');
			e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
			e.async = true;
			document.getElementById('fb-root').appendChild(e);
		}());
		</script>

		<a href="{$loginUrl}"><img src="http://static.ak.fbcdn.net/rsrc.php/zB6N8/hash/4li2k73z.gif"></a>
	</div>
</div>
