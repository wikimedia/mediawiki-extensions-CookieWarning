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

use Config;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\User\UserOptionsLookup;
use User;
use WebRequest;

class CookieWarning {
	/**
	 * @var Config|null The CookieWarning Config
	 */
	private $config;

	/**
	 * @var UserOptionsLookup
	 */
	private $userOptionsLookup;

	/**
	 * @var HookContainer
	 */
	private $hookContainer;

	/**
	 * CookieWarning constructor.
	 *
	 * @param Config $config
	 * @param UserOptionsLookup $userOptionsLookup
	 * @param HookContainer $hookContainer
	 */
	public function __construct( Config $config, UserOptionsLookup $userOptionsLookup, HookContainer $hookContainer ) {
		$this->config = $config;
		$this->userOptionsLookup = $userOptionsLookup;
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @return Config|null
	 */
	public function getConfig() {
		return $this->config;
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
	public function isAllowedCookie( string $cookieName, WebRequest $request, User $user ): bool {
		$prefix = $this->config->get( 'CookiePrefix' );
		$name = str_replace( $prefix, '', $cookieName );

		$preferences = [
			'required' => true,
			'preference' => false,
			'statistic' => false,
			'marketing' => false,
		];

		if ( $user->isRegistered() ) {
			$options = $this->userOptionsLookup->getOptions( $user );

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

		$requiredCookies = $this->config->get( 'CookieWarningRequiredCookies' );
		$preferenceCookies = $this->config->get( 'CookieWarningPreferenceCookies' );
		$statisticsCookies = $this->config->get( 'CookieWarningStatisticCookies' );
		$marketingCookies = $this->config->get( 'CookieWarningMarketingCookies' );

		$this->hookContainer->run( 'CookieListRetrieve', [
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

		$this->hookContainer->run( 'CookieAllowedCheck', [
			&$allowed,
			$cookieName,
			$preferences
		] );

		return $allowed;
	}
}
