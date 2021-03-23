<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

declare( strict_types=1 );

namespace MediaWiki\Extension\CookieWarning\Hooks;

use MediaWiki\Extension\CookieWarning\CookieWarning;
use MediaWiki\Hook\WebResponseSetCookieHook;
use RequestContext;

class CookieHooks implements WebResponseSetCookieHook {

	/**
	 * Stops the setting of all cookies that are not required
	 *
	 * @param string &$name
	 * @param string &$value
	 * @param int|null &$expire
	 * @param array &$options
	 * @return bool
	 */
	public function onWebResponseSetCookie( &$name, &$value, &$expire, &$options ) {
		$config = CookieWarning::getConfig();
		$context = RequestContext::getMain();

		if ( $context === null || $config === null || $config->get( 'CookieWarningUnsetCookies' ) === false ) {
			return true;
		}

		$allowed = CookieWarning::isAllowedCookie(
			$name,
			$context->getRequest(),
			$context->getUser()
		);

		if ( !$allowed ) {
			wfDebug( sprintf( 'Cookie "%s" is not on the list of allowed cookies. It will be unset.', $name ) );
		}

		return $allowed;
	}
}
