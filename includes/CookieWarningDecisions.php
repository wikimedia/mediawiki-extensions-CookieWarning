<?php

class CookieWarningDecisions {
	private $config;
	private $geoLocation;
	private $cache;

	const CACHE_KEY = 'cookieWarningIpLookupCache:';

	/**
	 * @param Config $config
	 * @param GeoLocation $geoLocation
	 * @param WANObjectCache $cache
	 */
	public function __construct( Config $config, GeoLocation $geoLocation, WANObjectCache $cache ) {
		$this->config = $config;
		$this->geoLocation = $geoLocation;
		$this->cache = $cache;
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
	public function shouldShowCookieWarning( IContextSource $context ) {
		$user = $context->getUser();

		return $this->config->get( 'CookieWarningEnabled' ) &&
			!$user->getBoolOption( 'cookiewarning_dismissed', false ) &&
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
	private function isInConfiguredRegion( IContextSource $context ) {
		if ( !$this->config->get( 'CookieWarningForCountryCodes' ) ||
			$this->config->get( 'CookieWarningGeoIPLookup' ) === 'none' ) {
			wfDebugLog( 'CookieWarning', 'IP geolocation not configured, skipping.' );

			return true;
		}

		$countryCode = $this->getCountryCodeFromIP( $context->getRequest()->getIP() );

		return array_key_exists( $countryCode,
			$this->config->get( 'CookieWarningForCountryCodes' ) );
	}

	/**
	 * @return bool
	 * @throws ConfigException
	 */
	public function shouldAddResourceLoaderComponents() {
		return $this->config->get( 'CookieWarningGeoIPLookup' ) === 'js' &&
			is_array( $this->config->get( 'CookieWarningForCountryCodes' ) );
	}

	/**
	 * @param $currentIP
	 * @return bool|mixed|null|string
	 * @throws ConfigException
	 */
	private function getCountryCodeFromIP( $currentIP ) {
		$cacheKey = $this->cache->makeGlobalKey( __CLASS__, self::CACHE_KEY . $currentIP );
		$lookedUpCountryCode = $this->cache->get( $cacheKey );

		if ( is_string( $lookedUpCountryCode ) ) {
			return $lookedUpCountryCode;
		}

		wfDebugLog( 'CookieWarning', 'Try to locate the user\'s IP address.' );
		$located = $this->geoLocation->locate( $currentIP );
		if ( !$located ) {
			wfDebugLog( 'CookieWarning',
				'Locating the user\'s IP address failed or is' . ' configured false.' );

			return true;
		}

		$lookedUpCountryCode = $this->geoLocation->getCountryCode();
		$this->cache->set( $cacheKey, $lookedUpCountryCode );

		wfDebugLog( 'CookieWarning', 'Locating the user was successful, located' . ' region: ' .
			$lookedUpCountryCode );

		return $lookedUpCountryCode;
	}
}
