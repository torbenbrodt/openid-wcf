<?php
// wcf imports
require_once(WCF_DIR.'lib/form/UserLoginForm.class.php');
require_once(WCF_DIR.'lib/data/openid/OpenID.class.php');

/**
 * handles authentication and user registration
 * 
 * @author	Torben Brodt
 * @copyright	2010 easy-coding.de
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.openid
 */
class OpenIDPage extends UserLoginForm {

	/**
	 * 
	 * @var string
	 */
	protected $identifier;
	
	/**
	 * @see Page::readParameters()
	 */
	public function readParameters() {
		parent::readParameters();
		
		$this->identifier = isset($_GET['identifier']) ? $_GET['identifier'] : null;
		
		$openid = new OpenID($this);
		
		if($this->identifier) {
			$openid->tryAuthentication($this->identifier);
		} else {
			$openid->finishAuthentication();
		}
	}
}
?>
