<?php
// wcf imports
require_once(WCF_DIR.'lib/page/OpenIDPage.class.php');
require_once(WCF_DIR.'lib/data/OpenIDProvider.class.php');

/**
 * HTTP response line contstants
 */
define('http_bad_request', 'HTTP/1.1 400 Bad Request');
define('http_found', 'HTTP/1.1 302 Found');
define('http_ok', 'HTTP/1.1 200 OK');
define('http_internal_error', 'HTTP/1.1 500 Internal Error');

/**
 * HTTP header constants
 */
define('header_connection_close', 'Connection: close');
define('header_content_text', 'Content-Type: text/plain; charset=us-ascii');
define('redirect_message', 'Please wait; you are being redirected to <%s>');

/**
 * allows authentication on other pages
 * 
 * @author	Torben Brodt
 * @copyright	2010 easy-coding.de
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.openid.provider
 */
class OpenIDServerPage extends OpenIDPage {
	public $action = 'index';

	/**
	 * @var OpenIDProvider
	 */
	private $provider = null;
	
	/**
	 * @var Auth_OpenID_Server
	 */
	private $server = null;

	/**
	 * wcf params
	 */
	public function readParameters() {
		parent::readParameters();

		if(isset($_GET['action'])) $this->action = $_GET['action'];
		
		$actions = array('index', 'login', 'trust', 'idpage', 'idpXrds', 'userXrds');
		if(!in_array($this->action, $actions)) {
			throw new Exception('unknown action: '.$this->action);
		}

		$this->provider = new OpenIDProvider();
		$this->server = $this->provider->getServer($this->buildURL());
	}

	/**
	 * Gets the redirect url.
	 */
	protected function checkURL() {
		$this->url = 'index.php?page=OpenIDProvider';
	}

	/**
	 * wcf action
	 */
	public function show() {
		parent::show();

		header('Cache-Control: no-cache');
		header('Pragma: no-cache');

		// call action
		$resp = $this->{$this->action}();

		// write response headers
		list ($headers, $body) = $resp;
		array_walk($headers, 'header');
		header('Connection: close');
		print $body;
	}

	/**
	 * Build a URL to a server action
	 */
	private function buildURL($action = null, $escaped = true) {
		$url = OpenIDProvider::getTrustRoot().'index.php?page=OpenIDProvider';
		if ($action) {
			$url .= '&action=' . $action;
		}
		return $escaped ? StringUtil::encodeHTML($url) : $url;
	}

	private function getRequestInfo() {
		$val = WCF::getSession()->getVar($name);
		return empty($val) ? false : unserialize($val);
	}

	private function setRequestInfo($info = null) {
		if ($info === null) {
			WCF::getSession()->unregister('request');
		} else {
			WCF::getSession()->register('request', serialize($info));
		}
	}

	/**
	 * Handle a standard OpenID server request
	 */
	private function index() {
		header('X-XRDS-Location: '.$this->buildURL('idpXrds'));

		$server = $this->server;
		$request = $server->decodeRequest();

		if (!$request) {
			return WCF::getTPL()->display('openIDProviderAbout');
		}

		$this->setRequestInfo($request);

		if (in_array($request->mode, array('checkid_immediate', 'checkid_setup'))) {

			if ($request->idSelect()) {
				// Perform IDP-driven identifier selection
				if ($request->mode == 'checkid_immediate') {
					$response =& $request->answer(false);
				} else {
					return WCF::getTPL()->display('openIDProviderTrust');
				}
			} else if (!$request->identity && !$request->idSelect()) {
				// No identifier used or desired; display a page saying so.
				return WCF::getTPL()->display('openIDProviderNoIdentifier');

			} else if ($request->immediate) {
				$response =& $request->answer(false, $this->buildURL());

			} else {
				if (!WCF::getUser()) {
					return WCF::getTPL()->display('openIDProviderLogin');
				}
				return WCF::getTPL()->display('openIDProviderTrust');
			}

		} else {
			$response =& $server->handleRequest($request);
		}

		$webresponse =& $server->encodeResponse($response);

		if ($webresponse->code != AUTH_OPENID_HTTP_OK) {
			header(sprintf("HTTP/1.1 %d ", $webresponse->code), true, $webresponse->code);
		}

		foreach ($webresponse->headers as $k => $v) {
			header("$k: $v");
		}

		header('Connection: close');
		print $webresponse->body;
		exit(0);
	}

	/**
	 * Log in a user and potentially continue the requested identity approval
	 */
	private function login() {
		$method = $_SERVER['REQUEST_METHOD'];
		switch ($method) {
			case 'GET':
				// display normal login
				break;
			case 'POST':
				// TODO: login form should redirect here
				$info = $this->getRequestInfo();
				return $this->doAuth($info);
				break;
			default:
				throw new Exception('Unsupported HTTP method: '.$method);
				break;
		}
	}

	/**
	 * Ask the user whether he wants to trust this site
	 */
	private function trust() {
		$info = $this->getRequestInfo();
		$trusted = isset($_POST['trust']);
		return doAuth($info, $trusted, true, @$_POST['idSelect']);
	}

	private function idpage() {
		$identity = $_GET['user'];
		
		$xrdsurl = buildURL('userXrds')."?user=".urlencode($identity);
		$headers = array('X-XRDS-Location: '.$xrdsurl);

		return array($headers, WCF::getTPL()->display('openIDProviderIdpage'));
	}

	private function idpXrds() {
		$headers = array('Content-type: application/xrds+xml');
		$body = sprintf(idp_xrds_pat, Auth_OpenID_TYPE_2_0_IDP);

		return array($headers, WCF::getTPL()->display('openIDProviderIdpXrds'));
	}

	private function userXrds() {
		$identity = $_GET['user'];
		$body = sprintf(user_xrds_pat, Auth_OpenID_TYPE_2_0, Auth_OpenID_TYPE_1_1);
		$headers = array('Content-type: application/xrds+xml');
		return array($headers, WCF::getTPL()->display('openIDProviderUserXrds'));
	}

	private function authCancel($info) {
		if ($info) {
			$this->setRequestInfo();
			$url = $info->getCancelURL();
		} else {
			$url = OpenIDProvider::getTrustRoot();
		}
		return redirect_render($url);
	}

	private function doAuth($info, $trusted=null, $fail_cancels=false, $idpSelect=null) {
		if (!$info) {
			// There is no authentication information, so bail
			return $this->authCancel(null);
		}

		if ($info->idSelect()) {
			if ($idpSelect) {
				$req_url = $this->idURL($idpSelect);
			} else {
				$trusted = false;
			}
		} else {
			$req_url = $info->identity;
		}

		$user = WCF::getUser();
		$this->setRequestInfo($info);

		if ((!$info->idSelect()) && ($req_url != $this->idURL($user))) {
			return WCF::getTPL()->display('openIDProviderLogin'); // (array(), $req_url, $req_url);
		}

		$trust_root = $info->trust_root;

		if ($trusted) {
			$this->setRequestInfo();
			$server =& $this->server;
			$response =& $info->answer(true, null, $req_url);

			// Answer with some sample Simple Registration data.
			$sreg_data = array(
				'fullname' => $user->fullname,
				'nickname' => $user->username,
				'dob' => $user->birthday,
				'email' => $user->email,
				'gender' => 'F', // $user->sex
				'postcode' => '12345',
				'country' => 'ES',
				'language' => 'eu',
				'timezone' => 'America/New_York'
			);

			// Add the simple registration response values to the OpenID
			// response message.
			$sreg_request = Auth_OpenID_SRegRequest::fromOpenIDRequest($info);

			$sreg_response = Auth_OpenID_SRegResponse::extractResponse($sreg_request, $sreg_data);

			$sreg_response->toMessage($response->fields);

			// Generate a response to send to the user agent.
			$webresponse =& $server->encodeResponse($response);

			$new_headers = array();
			foreach ($webresponse->headers as $k => $v) {
				$new_headers[] = $k.": ".$v;
			}

			return array($new_headers, $webresponse->body);
		} elseif ($fail_cancels) {

			return $this->authCancel($info);
		} else {

			return WCF::getTPL()->display('openIDProviderTrust'); // _render($info);
		}
	}

	private function idURL($identity) {
		return $this->buildURL('idpage') . "?user=" . ($identity ? $identity->username : '');
	}

	/**
	 * Return an HTTP redirect response
	 */
	private function redirect_render($redir_url) {
		$headers = array(
			http_found,
			header_content_text,
			header_connection_close,
			'Location: ' . $redir_url,
		);
		$body = sprintf(redirect_message, $redir_url);
		return array($headers, $body);
	}
}
