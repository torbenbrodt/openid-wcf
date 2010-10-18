<?php
// wcf imports
require_once(WCF_DIR.'lib/form/UserLoginForm.class.php');
require_once(WCF_DIR.'lib/data/openid/OpenID.class.php');

/**
 * handles reg and user registration
 * 
 * @author	Torben Brodt
 * @copyright	2010 easy-coding.de
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.openid
 */
class OpenIDPage extends UserLoginForm {
	protected $openid_action = '';

	/**
	 * 
	 * @var string
	 */
	protected $identifier;
	
	/**
	 * Creates a new LoginForm object.
	 */
	public function __construct() {
		
		// pass exception if userid > 0
		AbstractForm::__construct();
	}
	
	/**
	 * @see Page::readParameters()
	 */
	public function readParameters() {
		parent::readParameters();
		
		$this->identifier = isset($_GET['identifier']) ? $_GET['identifier'] : null;
		$this->callback = isset($_GET['callback']) && $_GET['callback'] ? true : false;
		$this->openid_action = isset($_GET['openid_action']) ? $_GET['openid_action'] : 'reg';
	}
	
	protected function finish($softRedirect = true) {
		if ($this->useCaptcha) {
			$this->captcha->delete();
		}
		WCF::getSession()->unregister('captchaDone');
		
		if($softRedirect) {
		
			// get redirect url
			$this->checkURL();
		
			// redirect to url
			WCF::getTPL()->assign(array(
				'url' => $this->url,
				'message' => WCF::getLanguage()->get('wcf.user.login.redirect'),
				'wait' => 5
			));
			WCF::getTPL()->display('redirect');
			exit;
		} else {
			header('Location: '.$this->url);
		}
	}
	
	/**
	 * @see Page::readData()
	 */
	public function readData() {
		parent::readData();
		
		try {
		
			$openid = new OpenID();
		
			switch($this->openid_action) {
				// reg
				case 'reg':
					$returnTo = PAGE_URL.'/index.php?page=OpenID&openid_action=reg&callback=1&identifier='.$this->identifier;
					if(!$this->callback) {
						$openid->tryRegistration($this->identifier, $returnTo);
					} else {
						$openid->finishRegistration($returnTo);
					
						$softRedirect = true;
						// proceed with attribution exchange url
						if(in_array($this->identifier, array('https://www.google.com/accounts/o8/id', 'http://yahoo.com/'))) {
							$this->url = PAGE_URL.'/index.php?page=OpenID&openid_action=ax&identifier='.$this->identifier;
							$softRedirect = false;
						}
					
						$this->finish($softRedirect);
					}
				break;
			
				// attribution exchange
				case 'ax':
					$returnTo = PAGE_URL.'/index.php?page=OpenID&openid_action=ax&callback=1&identifier='.$this->identifier;
					if(!$this->callback) {
						$openid->tryAttributionExchange($this->identifier, $returnTo);
					} else {
						$openid->finishAttributionExchange($returnTo);
					
						$this->finish();
					}
				break;
			}
		} catch(Exception $e) {
			WCF::getTPL()->append('userMessages', '<p class="error">'.$e->getMessage().'</p>');
		}
	}
}
?>
