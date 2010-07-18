<?php
require_once(WCF_DIR.'lib/data/user/UserEditor.class.php');

/**
 * embeds the openid system into the wcf
 * registers include pathes and cares for all dependencies
 * 
 * @author	Torben Brodt
 * @copyright	2010 easy-coding.de
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.openid
 */
class OpenID {

	/**
	 * form instance needed for finish action
	 *
	 * @var UserLoginForm|OpenIDPage
	 */
	protected $eventObj;

	/**
	 * sets include pathes
	 * 
	 * @param	UserLoginForm|OpenIDPage	$eventObj
	 */
	public function __construct($eventObj = null) {
		$this->eventObj = $eventObj;

		$path_extra = dirname(__FILE__);
		$path = ini_get('include_path');
		$path = $path_extra . PATH_SEPARATOR . $path;
		ini_set('include_path', $path);

		/**
		 * session wrapper
		 */
		require_once "Auth/Yadis/Manager.php";

		/**
		 * session wrapper
		 */
		require_once "OpenIDSession.class.php";

		/**
		 * Require the OpenID consumer code.
		 */
		require_once "Auth/OpenID/Consumer.php";

		/**
		 * Require the "file store" module, which we'll need to store
		 * OpenID information.
		 */
		require_once "Auth/OpenID/FileStore.php";

		/**
		 * Require the Simple Registration extension API.
		 */
		require_once "Auth/OpenID/SReg.php";

		/**
		 * Require the PAPE extension module.
		 */
		require_once "Auth/OpenID/PAPE.php";
	}

	/**
	 * returns file store
	 */
	protected function getStore() {
		$store_path = FileUtil::getTemporaryFilename('openid_');
		$store_path = TMP_DIR."/_openid".WCF_N;
		return new Auth_OpenID_FileStore($store_path);
	}

	/**
	 * get authenticated user
	 */
	protected function getConsumer() {

		$session = new OpenIDSession();
	
		/**
		 * Create a consumer object using the store object created
		 * earlier.
		 */
		$store = $this->getStore();
		$consumer =& new Auth_OpenID_Consumer($store, $session);
		return $consumer;
	}

	/**
	 * gets openid handler url
	 *
	 * @return	string
	 */
	public static function getReturnTo() {
		return PAGE_URL.'/index.php?page=OpenID';
	}

	/**
	 * gets root url
	 *
	 * @return	string
	 */
	public static function getTrustRoot() {
		return PAGE_URL;
	}

	/**
	 * call api and try authentication
	 */
	public function tryAuthentication($openid, $policy_uris = array()) {
		$consumer = $this->getConsumer();

		// Begin the OpenID authentication process.
		$auth_request = $consumer->begin($openid);

		// No auth request means we can't begin OpenID.
		if (!$auth_request) {
			throw new Exception("Authentication error; not a valid OpenID.");
		}

		$sreg_request = Auth_OpenID_SRegRequest::build(
			 // Required
			 array('nickname'),
			 // Optional
			 array('email')
		);

		if ($sreg_request) {
			$auth_request->addExtension($sreg_request);
		}

		$pape_request = new Auth_OpenID_PAPE_Request($policy_uris);
		if ($pape_request) {
			$auth_request->addExtension($pape_request);
		}

		// Redirect the user to the OpenID server for authentication.
		// Store the token for this authentication so we can verify the
		// response.

		// For OpenID 1, send a redirect.  For OpenID 2, use a Javascript
		// form to send a POST request to the server.
		if ($auth_request->shouldSendRedirect()) {
			$redirect_url = $auth_request->redirectURL(self::getTrustRoot(), self::getReturnTo());

			// If the redirect URL can't be built, display an error
			// message.
			if (Auth_OpenID::isFailure($redirect_url)) {
				throw new Exception("Could not redirect to server: " . $redirect_url->message);
			} else {
				// Send redirect.
				header("Location: ".$redirect_url);
				exit;
			}
		} else {
			// Generate form markup and render it.
			$form_id = 'openid_message';
			$form_html = $auth_request->htmlMarkup(self::getTrustRoot(), self::getReturnTo(), false, array('id' => $form_id));

			// Display an error if the form markup couldn't be generated;
			// otherwise, render the HTML.
			if (Auth_OpenID::isFailure($form_html)) {
				throw new Exception("Could not redirect to server: " . $form_html->message);
			} else {
			
				// used by openid 2, formular and redirect are printed out
				echo $form_html;
				exit;
			}
		}
	}

	/**
	 * got answer, save user
	 */
	public function finishAuthentication() {
		$consumer = $this->getConsumer();

		// Complete the authentication process using the server's
		// response.
		$return_to = self::getReturnTo();
		$response = $consumer->complete($return_to);

		// Check the response status.
		if ($response->status == Auth_OpenID_CANCEL) {
			// This means the authentication was cancelled.
			$msg = 'Verification cancelled.';
		} else if ($response->status == Auth_OpenID_FAILURE) {
			// Authentication failed; display the error message.
			$msg = "OpenID authentication failed: " . $response->message;
		} else if ($response->status == Auth_OpenID_SUCCESS) {
			// This means the authentication succeeded; extract the
			// identity URL and Simple Registration data (if it was
			// returned).
			$openid = $response->getDisplayIdentifier();
			$esc_identity = StringUtil::encodeHTML($openid);

			$success = sprintf('You have successfully verified ' .
				'<a href="%s">%s</a> as your identity.',
				$esc_identity, $esc_identity);

			if ($response->endpoint->canonicalID) {
				$encoded_canonicalID = StringUtil::encodeHTML($response->endpoint->canonicalID);
				$success .= '  (XRI CanonicalID: '.$encoded_canonicalID.') ';
			}

			$pape_resp = Auth_OpenID_PAPE_Response::fromSuccessResponse($response);

			if ($pape_resp) {
				if ($pape_resp->auth_policies) {
					$success .= "<p>The following PAPE policies affected the authentication:</p><ul>";

					foreach ($pape_resp->auth_policies as $uri) {
						$encoded_uri = StringUtil::encodeHTML($uri);
						$success .= "<li><tt>$encoded_uri</tt></li>";
					}

					$success .= "</ul>";
				} else {
					$success .= "<p>No PAPE policies affected the authentication.</p>";
				}

				if ($pape_resp->auth_age) {
					$age = StringUtil::encodeHTML($pape_resp->auth_age);
					$success .= "<p>The authentication age returned by the " .
						"server is: <tt>".$age."</tt></p>";
				}

				if ($pape_resp->nist_auth_level) {
					$auth_level = StringUtil::encodeHTML($pape_resp->nist_auth_level);
					$success .= "<p>The NIST auth level returned by the " .
						"server is: <tt>".$auth_level."</tt></p>";
				}

			} else {
				$success .= "<p>No PAPE response was sent by the provider.</p>";
			}
			
			$sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);
			$sreg = $sreg_resp->contents();
			
			// save user authentication
			$this->finishUser(array(
				'name' => isset($sreg['nickname']) ? $sreg['nickname'] : null,
				'email' => isset($sreg['email']) ? $sreg['email'] : null,
				'identifier' => $openid
			));
		}
	}

	/**
	 * is there an existing user with given openid id?
	 *
	 * @param	array		$me
	 * @return	User
	 */
	protected function getOpenIDEnabledUser($me) {
		$sql = "SELECT		userID
			FROM 		wcf".WCF_N."_user_to_openid
			WHERE		openID = '".sha1($me['identifier'])."'";
		$row = WCF::getDB()->getFirstRow($sql);

		$user = $row ? new User($row['userID']) : null;
		return $user && $user->userID ? $user : null;
	}

	/**
	 * adds openid link to user
	 *
	 * @param	array		$me
	 * @param	User		$user
	 * @return	boolean
	 */
	protected function addOpenIDUser($me, $user) {
		$sql = "REPLACE INTO	wcf".WCF_N."_user_to_openid
					(openID, userID)
			VALUES		('".sha1($me['identifier'])."', ".intval($user->userID).")";

		return WCF::getDB()->sendQuery($sql);
	}

	/**
	 * save incoming user
	 */
	public function finishUser($me) {

		// take default username from hostname
		if($me['name'] === null) {
			$host = parse_url($me['identifier'], PHP_URL_HOST)." ID #1";
			$host = preg_replace("/^www\./", "", $host);
			$me['name'] = $host;
		}
		
		// openid permissions granted, does an login exist?
		$user = $this->getOpenIDEnabledUser($me);

		// openid permissions granted but no login exists
		if(!$user) {

			// totally unknown, add a new user
			$user = $this->registerUser($me);

			// either user is new, oder just got a link, but add a openid link
			$this->addOpenIDUser($me, $user);
		}

		if($user) {

			// UserLoginForm should not write cookie, since interfaces only support unhashed password
			$this->eventObj->useCookies = 0;

			// set cookies
			UserAuth::getInstance()->storeAccessData($user, $user->username, $user->password);
			HeaderUtil::setCookie('password', $user->password, TIME_NOW + 365 * 24 * 3600);

			// save cookie and redirect
			$this->eventObj->user = $user;
			$this->eventObj->save();

			exit;
		}
	}

	/**
	 * get a available username
	 *
	 * @param	string		$username
	 * @return	string
	 */
	protected function findUsername($username) {
		if(!UserUtil::isValidUsername($username)) {
			return null;
		}

		if(UserUtil::isAvailableUsername($username)) {
			return $username;
		}

		// try to increase last digit
		if(preg_match('/(\d+)$/', $username, $res)) {
			return $this->findUsername(preg_replace('/(\d+)/', ($res[1] + 1), $username));
		} else {
			return $this->findUsername($username.'2');
		}
	}

	/**
	 * registers a new user with valid username
	 *
	 * @param	array		$me
	 * @return	User
	 */
	protected function registerUser($me) {
		$user = null;
		// get a valid username
		$username = $this->findUsername($me['name']);
		
		// take default email
		if($me['email'] === null) {
			$host = parse_url($me['identifier'], PHP_URL_HOST);
			$host = preg_replace("/^www\./", "", $host);
			$me['email'] = md5($me['identifier']).'@openid.'.$host;
		}

		// create new user
		if($username) {
			$user = $this->createNewUser(
				$username,
				$me['email']
			);
		} else {
			throw new SystemException('invalid openid username: '.$me['name']);
		}
		return $user;
	}

	/**
	 * adds a new wcf user and sends, bypasses all registration steps and send out mails
	 *
	 * @param	string		$username
	 * @param	string		$email
	 * @return	User
	 */
	protected function createNewUser($username, $email) {

		$password = UserRegistrationUtil::getNewPassword((REGISTER_PASSWORD_MIN_LENGTH > 9 ? REGISTER_PASSWORD_MIN_LENGTH : 9));
		$groups = array();
		$activeOptions = array();

		$additionalFields = array();
		$additionalFields['languageID'] = WCF::getLanguage()->getLanguageID();
		$additionalFields['registrationIpAddress'] = WCF::getSession()->ipAddress;

		$visibleLanguages = $this->getAvailableLanguages();
		$visibleLanguages = array_keys($visibleLanguages);
		$addDefaultGroups = true;

		// create the user
		if(($user = UserEditor::create($username, $email, $password, $groups, $activeOptions, $additionalFields, $visibleLanguages, $addDefaultGroups))) {

			// notify admin
			if (REGISTER_ADMIN_NOTIFICATION) {
				// get default language
				$language = (WCF::getLanguage()->getLanguageID() != Language::getDefaultLanguageID()
					? new Language(Language::getDefaultLanguageID())
					: WCF::getLanguage());
				$language->setLocale();

				// send mail
				$mail = new Mail(
					MAIL_ADMIN_ADDRESS,
					$language->get('wcf.user.register.notification.mail.subject', array(
						'PAGE_TITLE' => $language->get(PAGE_TITLE)
					)),
					$language->get('wcf.user.register.notification.mail', array(
						'PAGE_TITLE' => $language->get(PAGE_TITLE),
						'$username' => $user->username
					))
				);
				$mail->send();

				WCF::getLanguage()->setLocale();
			}
		}

		return $user;
	}

	/**
	 * Returns a list of all available languages.
	 *
	 * @return	array
	 */
	protected function getAvailableLanguages() {
		$availableLanguages = array();
		foreach (Language::getAvailableLanguages(PACKAGE_ID) as $language) {
			$availableLanguages[$language['languageID']] = WCF::getLanguage()->get('wcf.global.language.'.$language['languageCode']);
		}

		// sort languages
		StringUtil::sort($availableLanguages);

		return $availableLanguages;
	}
}
