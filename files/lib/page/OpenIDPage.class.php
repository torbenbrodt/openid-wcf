<?php
// wcf imports
require_once(WCF_DIR.'lib/page/AbstractPage.class.php');
require_once(WCF_DIR.'lib/data/openid/OpenID.class.php');

/**
 * handles authentication and user registration
 * 
 * @author	Torben Brodt
 * @copyright	2010 easy-coding.de
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.openid
 */
class OpenIDPage extends AbstractPage {

	protected $identifier;
	
	/**
	 * @see Page::readParameters()
	 */
	public function readParameters() {
		parent::readParameters();
		
		$this->identifier = isset($_GET['identifier']) ? $_GET['identifier'] : null;
	}
	
	/**
	 * @see Page::readData()
	 */
	public function readData() {
		parent::readData();
		
		$openid = new OpenID();
		
		if($this->identifier) {
			$openid->tryAuthentication($this->identifier);
		} else {
			$openid->finishAuthentication();
		}
	}
	
	/**
	 * @see Page::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		
		WCF::getTPL()->assign(array(
			'entry' => $this->entry,
		));
	}
}
?>
