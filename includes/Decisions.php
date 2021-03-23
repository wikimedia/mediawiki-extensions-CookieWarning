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
use ConfigException;
use IContextSource;
use MediaWiki\Extension\CookieWarning\GeoLocation\GeoLocation;
use MediaWiki\User\UserOptionsLookup;
use MWException;
use WANObjectCache;

class Decisions {
	private $config;
	private $geoLocation;
	private $cache;
	private $userOptionsLookup;

	private const CACHE_KEY = 'cookieWarningIpLookupCache:';

	/**
	 * @param Config $config
	 * @param GeoLocation $geoLocation
	 * @param WANObjectCache $cache
	 * @param UserOptionsLookup $userOptionsLookup
	 */
	public function __construct(
		Config $config,
		GeoLocation $geoLocation,
		WANObjectCache $cache,
		UserOptionsLookup $userOptionsLookup
	) {
		$this->config = $config;
		$this->geoLocation = $geoLocation;
		$this->cache = $cache;
		$this->userOptionsLookup = $userOptionsLookup;
	}

	/**
	 * Checks, if the CookieWarning information bar should be visible to this user on
	 * this page.
	 *
	 * @param IContextSource $context
	 * @return bool Returns true, if the cookie warning should be visible, false otherwise.
	 * @throws ConfigException
	 * @throws MWException
	 */
	public function shouldShowCookieWarning( IContextSource $context ): bool {
		$user = $context->getUser();

		return $this->config->get( 'CookieWarningEnabled' ) &&
			!$this->userOptionsLookup->getBoolOption( $user, 'cookiewarning_dismissed' ) &&
			!$context->getRequest()->getCookie( 'cookiewarning_dismissed' ) &&
			( $this->config->get( 'CookieWarningGeoIPLookup' ) === 'js' ||
				$this->isInConfiguredRegion( $context ) );
	}

	/**
	 * Checks, if the user is in one of the configured regions.
	 *
	 * @param IContextSource $context
	 * @return bool
	 * @throws ConfigException
	 * @throws MWException
	 */
	private function isInConfiguredRegion( IContextSource $context ): bool {
		if ( !$this->config->get( 'CookieWarningForCountryCodes' ) ||
			$this->config->get( 'CookieWarningGeoIPLookup' ) === 'none' ) {
			wfDebugLog( 'CookieWarning', 'IP geolocation not configured, skipping.' );

			return true;
		}

		$countryCode = $this->getCountryCodeFromIP( $context->getRequest()->getIP() );

		return $countryCode === '' || array_key_exists( $countryCode,
			$this->config->get( 'CookieWarningForCountryCodes' ) );
	}

	/**
	 * @return bool
	 * @throws ConfigException
	 */
	public function shouldAddResourceLoaderComponents(): bool {
		return $this->config->get( 'CookieWarningGeoIPLookup' ) === 'js' &&
			is_array( $this->config->get( 'CookieWarningForCountryCodes' ) );
	}

	/**
	 * @param string $currentIP
	 * @return string The country code associated with the IP or empty string if not able to locate.
	 * @throws ConfigException
	 */
	private function getCountryCodeFromIP( string $currentIP ): string {
		$cacheKey = $this->cache->makeGlobalKey( __CLASS__, self::CACHE_KEY . $currentIP );
		$lookedUpCountryCode = $this->cache->get( $cacheKey );

		if ( is_string( $lookedUpCountryCode ) ) {
			return $lookedUpCountryCode;
		}

		wfDebugLog( 'CookieWarning', 'Try to locate the user\'s IP address.' );
		$location = $this->geoLocation->locate( $currentIP );
		if ( $location === null ) {
			wfDebugLog( 'CookieWarning',
				'Locating the user\'s IP address failed or is misconfigured.' );

			return '';
		}

		$this->cache->set( $cacheKey, $location );

		wfDebugLog( 'CookieWarning',
			'Locating the user was successful, located region: ' . $location );

		return $location;
	}
}
