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

interface CookieAllowedCheckHook {

	/**
	 * This is run before finally deciding if the cookie name is allowed
	 * The value of allowed can be updated to let a cookie pass or to delete it
	 *
	 * @param bool &$allowed Flag to allow/disable the cookie
	 * @param string $cookieName Name of the cookie
	 * @param array $preferences Preferences of the user
	 */
	public function onCookieAllowedCheck( bool &$allowed, string $cookieName, array $preferences ): void;
}
