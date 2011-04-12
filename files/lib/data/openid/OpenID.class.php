<?php
require_once(WCF_DIR.'lib/data/user/UserEditor.class.php');
require_once(WCF_DIR.'lib/util/UserUtil.class.php');

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
	 * sets include pathes
	 * 
	 */
	public function __construct() {

		// fix for windows
		if(!file_exists('/dev/urandom')) {
			define('Auth_OpenID_RAND_SOURCE', null);
		}

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

		/**
		 * Attribution Exchange
		 */
		require_once "Auth/OpenID/AX.php";
	}

	/**
	 * returns file store (tempoary store object)
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
		$consumer = new Auth_OpenID_Consumer($store, $session);
		return $consumer;
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
	public function tryRegistration($openid, $returnTo, $policy_uris = array()) {
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
			$redirect_url = $auth_request->redirectURL(self::getTrustRoot(), $returnTo);

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
			$form_html = $auth_request->htmlMarkup(self::getTrustRoot(), $returnTo, false, array('id' => $form_id));

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
	public function finishRegistration($return_to) {
		$consumer = $this->getConsumer();

		// Complete the authentication process using the server's
		// response.
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

			if ($response->endpoint->canonicalID) {
				$encoded_canonicalID = StringUtil::encodeHTML($response->endpoint->canonicalID);
			}

			$sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);
			$sreg = $sreg_resp->contents();

			// save user authentication
			$user = $this->finishUser(array(
				'name' => isset($sreg['nickname']) ? $sreg['nickname'] : null,
				'email' => isset($sreg['email']) ? $sreg['email'] : null,
				'identifier' => $openid
			));

			if($user) {
				// set cookies
				UserAuth::getInstance()->storeAccessData($user, $user->username, $user->password);
				HeaderUtil::setCookie('password', $user->password, TIME_NOW + 365 * 24 * 3600);

				// change user
				WCF::getSession()->changeUser($user);
			}
		}
	}

	public function tryAttributionExchange($openid, $returnTo) {
		$consumer = $this->getConsumer();

		// Create an authentication request to the OpenID provider
		$auth = $consumer->begin($openid);

		// Create attribute request object
		// See http://code.google.com/apis/accounts/docs/OpenID.html#Parameters for parameters
		// Usage: make($type_uri, $count=1, $required=false, $alias=null)
		$attribute = array();
		$attribute[] = Auth_OpenID_AX_AttrInfo::make('http://axschema.org/contact/email', 1, 1, 'email');
		$attribute[] = Auth_OpenID_AX_AttrInfo::make('http://axschema.org/namePerson/first', 1, 1, 'firstname');
		$attribute[] = Auth_OpenID_AX_AttrInfo::make('http://axschema.org/namePerson/last', 1, 1, 'lastname');

		// Create AX fetch request
		$ax = new Auth_OpenID_AX_FetchRequest();

		// Add attributes to AX fetch request
		foreach($attribute as $attr){
			$ax->add($attr);
		}

		// Add AX fetch request to authentication request
		$auth->addExtension($ax);

		// Redirect to OpenID provider for authentication
		$url = $auth->redirectURL(self::getTrustRoot(), $returnTo);
		header('Location: ' . $url);
		exit;
	}

	public function finishAttributionExchange($return_to) {
		$consumer = $this->getConsumer();

		// Create an authentication request to the OpenID provider
		$response = $consumer->complete($return_to);

		if ($response->status == Auth_OpenID_SUCCESS) {

			// Get registration informations
			$ax = new Auth_OpenID_AX_FetchResponse();
			$obj = $ax->fromSuccessResponse($response);

			// Print me raw
			$me = array();
			foreach($obj->data as $key => $val) {
				if(!isset($val[0])) {
					continue;
				}
				$key = substr($key, strrpos($key, '/') + 1);
				$me[$key] = $val[0];
			}

			$userID = WCF::getUser()->userID;
			if($userID) {
				$editor = WCF::getUser()->getEditor();

				// only update username, if old username is still hashed
				$username = '';
				if(preg_match('/#\d+$/', $editor->username)) {
					if(isset($me['first'])) {
						$username .= ucfirst($me['first']);
					}
					if(isset($me['last'])) {
						$username .= ucfirst($me['last']);
					}
					if(!empty($username)) {
						$username = $this->findUsername($username);
					}
				}

				// update email address
				$email = '';
				if(isset($me['email']) && UserUtil::isValidEmail($me['email']) && UserUtil::isAvailableEmail($me['email'])) {
					$email = $me['email'];
				}

				if($username || $email) {
					$editor->update($username, $email);
					WCF::getSession()->updateUserData();
				}
			}
		}
	}

	/**
	 * is there an existing user with given facebook id?
	 *
	 * @param	integer		$userID
	 * @return	boolean
	 */
	public static function hasOpenIDAccount($userID) {
		$sql = "SELECT		uto.userID
			FROM 		wcf".WCF_N."_user_to_openid uto
			WHERE		utb.userID = ".intval($userID);
		$row = WCF::getDB()->getFirstRow($sql);

		return $row && $row['userID'] > 0;
	}

	/**
	 * is there an existing user with given openid id?
	 *
	 * @param	array		$me
	 * @return	User
	 */
	protected static function getOpenIDEnabledUser($me) {
		$sql = "SELECT		userID
			FROM 		wcf".WCF_N."_user_to_openid uto
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
	protected function finishUser($me) {

		// take default username from hostname
		if($me['name'] === null) {
			$host = parse_url($me['identifier'], PHP_URL_HOST)." ID #1";
			$host = preg_replace("/^www\./", "", $host);
			$me['name'] = $host;
		}

		// openid permissions granted, does an login exist?
		$user = self::getOpenIDEnabledUser($me);

		// openid permissions granted but no login exists
		if(!$user) {

			// totally unknown, add a new user
			$user = $this->registerUser($me);

			// either user is new, oder just got a link, but add a openid link
			$this->addOpenIDUser($me, $user);
		}

		return $user;
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
		if($me['email'] === null || !UserUtil::isValidEmail($me['email'])) {
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
