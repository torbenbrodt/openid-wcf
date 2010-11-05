<?php
require_once(WCF_DIR.'lib/data/openid/OpenID.class.php');

/**
 * embeds the openid system into the wcf
 * registers include pathes and cares for all dependencies
 * 
 * @author	Torben Brodt
 * @copyright	2010 easy-coding.de
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.openid.provider
 */
class OpenIDProvider extends OpenID {

	/**
	 * sets include pathes
	 * 
	 */
	public function __construct() {
		parent::__construct();

		/**
		 * Register Server
		 */
		require_once "Auth/OpenID/Server.php";
		
		require_once "Auth/OpenID/Discover.php";
	}

	/**
	 * returns file store (persistent store object)
	 * TODO: make persistent
	 */
	protected function getStore() {
		$store_path = FileUtil::getTemporaryFilename('openid_');
		$store_path = TMP_DIR."/_openid".WCF_N;
		return new Auth_OpenID_FileStore($store_path);
	}

	/**
	 * Instantiate a new OpenID server object
	 */
	public function getServer($url) {

		$session = new OpenIDSession();
	
		/**
		 * Create a consumer object using the store object created
		 * earlier.
		 */
		$store = $this->getStore();
		$server =& new Auth_OpenID_Server($store, $url);
		return $server;
	}
}
