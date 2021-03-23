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

interface CookieListRetrieveHook {

	/**
	 * This is run before checking the current cookie against all four lists
	 * The arrays shall only contain name of cookies
	 *
	 * @param array &$required
	 * @param array &$preference
	 * @param array &$statistics
	 * @param array &$marketing
	 */
	public function onCookieListRetrieve( array &$required, array &$preference, array &$statistics, array &$marketing ): void;
}
