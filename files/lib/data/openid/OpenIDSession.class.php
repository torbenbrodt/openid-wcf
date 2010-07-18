<?php

/**
 * map wcf session management to open id classes
 * 
 * @author	Torben Brodt
 * @copyright	2010 easy-coding.de
 * @license	GNU General Public License <http://opensource.org/licenses/gpl-3.0.html>
 * @package	de.easy-coding.wcf.openid
 */
class OpenIDSession extends Auth_Yadis_PHPSession {

	/**
	 * Set a session key/value pair.
	 *
	 * @param string $name The name of the session key to add.
	 * @param string $value The value to add to the session.
	 */
	function set($name, $value) {
		WCF::getSession()->register($name, $value);
	}

	/**
	 * Get a key's value from the session.
	 *
	 * @param string $name The name of the key to retrieve.
	 * @param string $default The optional value to return if the key
	 * is not found in the session.
	 * @return string $result The key's value in the session or
	 * $default if it isn't found.
	 */
	function get($name, $default=null) {
		$val = WCF::getSession()->getVar($name);
		return empty($val) ? $default : $val;
	}

	/**
	 * Remove a key/value pair from the session.
	 *
	 * @param string $name The name of the key to remove.
	 */
	function del($name) {
		WCF::getSession()->unregister($name);
	}

	/**
	 * Return the contents of the session in array form.
	 */
	function contents() {
		return WCF::getSession()->getVars();
	}
}
?>
