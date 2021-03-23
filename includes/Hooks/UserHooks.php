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

use MediaWiki\Hook\UserLoginCompleteHook;
use MediaWiki\MediaWikiServices;
use RequestContext;

class UserHooks implements UserLoginCompleteHook {

	/**
	 * Save the preferences found in the preference cookie to the database
     * Mainly used for when the user accepts the cookies and then logs in
	 *
	 * @inheritDoc
	 */
	public function onUserLoginComplete( $user, &$inject_html, $direct ) {
		$context = RequestContext::getMain();
		if ( $context === null || $context->getRequest()->getCookie( 'cookiewarning_preferences' ) === null ) {
			return;
		}

		$prefix = MediaWikiServices::getInstance()->getMainConfig()->get( 'CookiePrefix' );

		$decoded = json_decode( $context->getRequest()->getCookie( 'cookiewarning_preferences', $prefix ), true );
		if ( $decoded === null ) {
			return;
		}

		$manager = MediaWikiServices::getInstance()->getUserOptionsManager();
		$manager->setOption(
			$user,
			'cookiewarning_cookies_required',
			true
		);
		$manager->setOption(
			$user,
			'cookiewarning_cookies_preference',
			( $decoded['preference'] === null ) ? false : (bool)$decoded['preference']
		);
		$manager->setOption(
			$user,
			'cookiewarning_cookies_statistic',
			( $decoded['statistic'] === null ) ? false : (bool)$decoded['statistic']
		);
		$manager->setOption(
			$user,
			'cookiewarning_cookies_marketing',
			( $decoded['marketing'] === null ) ? false : (bool)$decoded['marketing']
		);
	}
}
