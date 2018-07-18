<?php

class CookieWarningDecisions {
	private $config;
	private $geoLocation;

	/**
	 * @param Config $config
	 * @param GeoLocation $geoLocation
	 */
	public function __construct( Config $config, GeoLocation $geoLocation ) {
		$this->config = $config;
		$this->geoLocation = $geoLocation;
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
	 * @TODO: This function or the function users should set the cookie or user option, if this
	 * function returns false to avoid a location lookup on each request.
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

		wfDebugLog( 'CookieWarning', 'Try to locate the user\'s IP address.' );
		$located = $this->geoLocation->locate( $context->getRequest()->getIP() );
		if ( !$located ) {
			wfDebugLog( 'CookieWarning',
				'Locating the user\'s IP address failed or is' . ' configured false.' );

			return true;
		}
		wfDebugLog( 'CookieWarning', 'Locating the user was successful, located' . ' region: ' .
			$this->geoLocation->getCountryCode() );

		return array_key_exists( $this->geoLocation->getCountryCode(),
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
}
