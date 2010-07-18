<?php
// wcf imports
require_once(WCF_DIR.'lib/page/AbstractPage.class.php');

/**
 * 
 * 
 * @author	Torben Brodt
 * @copyright	2010 easy-coding.de
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.openid
 */
class OpenIDPage extends AbstractPage {

	
	/**
	 * @see Page::readParameters()
	 */
	public function readParameters() {
		parent::readParameters();
		
		
	}
	
	/**
	 * @see Page::readData()
	 */
	public function readData() {
		parent::readData();
		
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
