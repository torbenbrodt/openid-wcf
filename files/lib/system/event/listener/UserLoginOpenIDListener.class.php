<?php
require_once(WCF_DIR.'lib/system/event/EventListener.class.php');
require_once(WCF_DIR.'lib/data/openid/OpenID.class.php');

/**
 * login will display openid login button and manage all the login stuff
 * 
 * @author	Torben Brodt
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.openid
 */
class UserLoginOpenIDListener implements EventListener {

	/**
	 * @see EventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		if (!MODULE_OPENID) {
			return;
		}

		switch($className) {
			case 'UserLoginForm':
			case 'OpenIDPage':
				// assignVariables
				WCF::getTPL()->assign(array(
					'openid_url' => PAGE_URL.'/index.php?page=OpenID',
				));

				WCF::getTPL()->append('additionalFields', WCF::getTPL()->fetch('openidLogin'));
			break;
	}
}
?>
