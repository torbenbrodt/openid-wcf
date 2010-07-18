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
	 * from eventlistener
	 *
	 * @var UserLoginForm
	 */
	protected $eventObj;
	
	/**
	 * from eventlistener
	 *
	 * @var string
	 */
	protected $className;

	/**
	 * @see EventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		if (!MODULE_OPENID) {
			return;
		}

		$this->eventObj = $eventObj;
		$this->className = $className;

		if(method_exists($this, $eventName)) {
			$this->$eventName();
		}
	}

	/**
	 * @see UserLoginForm::assignVariables
	 */
	public function assignVariables() {

		WCF::getTPL()->assign(array(
			'openid_url' => OpenID::getReturnTo(),
		));

		WCF::getTPL()->append('additionalFields', WCF::getTPL()->fetch('openidLogin'));
	}
}
?>
