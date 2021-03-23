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

namespace MediaWiki\Extension\CookieWarning;

use MediaWiki\Extension\CookieWarning\Hooks\CookieAllowedCheckHook;
use MediaWiki\Extension\CookieWarning\Hooks\CookieListRetrieveHook;
use MediaWiki\MediaWikiServices;
use User;
use WebRequest;

class CookieWarning {
	/**
	 * @return mixed|null
	 */
	public static function getConfig() {
		return MediaWikiServices::getInstance()->getService( 'CookieWarning.Config' );
	}

	/**
	 * Returns true if the provided cookie name is an allowed cookie
	 * The name is checked against the list of required, preference, statistic and marketing cookie names and
	 * if the user has enabled one of those groups
	 *
	 * @param string $cookieName
	 * @param WebRequest $request
	 * @param User $user
	 * @return bool
	 */
	public static function isAllowedCookie( string $cookieName, WebRequest $request, User $user ): bool {
		$config = self::getConfig();
		$prefix = MediaWikiServices::getInstance()->getMainConfig()->get( 'CookiePrefix' );
		$name = str_replace( $prefix, '', $cookieName );

		$preferences = [
			'required' => true,
			'preference' => false,
			'statistic' => false,
			'marketing' => false,
		];

		if ( $user->isLoggedIn() ) {
			$options = MediaWikiServices::getInstance()->getUserOptionsManager()->getOptions( $user );

			$preferences['preference'] = $options['cookiewarning_cookies_preference'] ?? false;
			$preferences['statistic'] = $options['cookiewarning_cookies_statistic'] ?? false;
			$preferences['marketing'] = $options['cookiewarning_cookies_marketing'] ?? false;
		} elseif ( $request->getCookie( 'cookiewarning_preferences', $prefix ) !== null ) {
			$decoded = json_decode( $request->getCookie( 'cookiewarning_preferences', $prefix ), true );
			if ( $decoded !== null ) {
				$preferences['preference'] = $decoded['preference'] ?? false;
				$preferences['statistic'] = $decoded['statistic'] ?? false;
				$preferences['marketing'] = $decoded['marketing'] ?? false;
			}
		}

		$allowed = false;

		$requiredCookies = $config->get( 'CookieWarningRequiredCookies' );
		$preferenceCookies = $config->get( 'CookieWarningPreferenceCookies' );
		$statisticsCookies = $config->get( 'CookieWarningStatisticCookies' );
		$marketingCookies = $config->get( 'CookieWarningMarketingCookies' );

		MediaWikiServices::getInstance()->getHookContainer()->run( CookieListRetrieveHook::class, [
			&$requiredCookies,
			&$preferenceCookies,
			&$statisticsCookies,
			&$marketingCookies,
		] );

		if ( in_array( $name, $requiredCookies, true ) ) {
			$allowed = true;
		}

		if ( $preferences['preference'] === true && in_array( $name, $preferenceCookies, true ) ) {
			$allowed = true;
		}

		if ( $preferences['statistic'] === true && in_array( $name, $statisticsCookies, true ) ) {
			$allowed = true;
		}

		if ( $preferences['marketing'] === true && in_array( $name, $marketingCookies, true ) ) {
			$allowed = true;
		}

		MediaWikiServices::getInstance()->getHookContainer()->run( CookieAllowedCheckHook::class, [
			&$allowed,
			$cookieName,
			$preferences
		] );

		return $allowed;
	}
}
