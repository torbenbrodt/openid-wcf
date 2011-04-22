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
	protected static $ignoreForms = array(
		'rulesagree',
		'userprofileedit',
		'accountmanagement',
	);
	protected static $ignorePages = array(
		'legalnotice'
	);

	/**
	 * @see EventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		if (!MODULE_OPENID) {
			return;
		}

		switch($className) {
			
			// did agree with rules?
			case 'SessionFactory':
				// didInit
				$this->validateRuleAgree($eventObj->session);
			break;
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

	/**
	 * 
	 */
	protected function validateRuleAgree($session) {
		
		// if the modul deactivated, or the user must no agree the rules, we can leave the event.
		// if we log out or on the rulesagree page, we also leave the event.
		if (!defined('MODULE_RULE') || MODULE_RULE == 0 || $session->getUser()->getPermission('admin.general.canIgnoreRules')) return;
		if ((isset($_REQUEST['action']) && strtolower($_REQUEST['action']) == 'userlogout') || (isset($_REQUEST['form']) && in_array(strtolower($_REQUEST['form']), self::$ignoreForms)) || (isset($_REQUEST['page']) && in_array(strtolower($_REQUEST['page']), self::$ignorePages))) return;
		
		// if the modul activate and the user is openid user he must agree the rules after a change, and the user is not a guest.
		if ($session->getUser()->userID && OpenID::hasOpenIDAccount($session->getUser()->userID)) {
			// select all packageids of the packages where the user is agree with the rules.
			$packageIDs = $session->getVar('package_agrees');
			
			// if the packageid array null or the current package is not in the id, must check the agreement.
			if (is_null($packageIDs) || !in_array(PACKAGE_ID, $packageIDs)) {
				// we check the agreement, is the user agree with the rules, we put the package id in to the array and leave the event.
				if (Ruleset::isUserAgree($session->getUser()->userID, PACKAGE_ID)) {
					if (is_null($packageIDs) || !is_array($packageIDs)) 
						$packageIDs = array(PACKAGE_ID);
					else $packageIDs[] = PACKAGE_ID;
						$session->register('package_agrees', $packageIDs);
					return;
				}
				HeaderUtil::redirect('index.php?form=RulesAgree'.SID_ARG_2ND_NOT_ENCODED, false);
				exit;
			}
		}
	}
}
?>
