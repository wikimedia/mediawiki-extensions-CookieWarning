<?php

namespace CookieWarning;

/**
 * GeoLocation implementation
 */

use Config;
use ConfigException;
use Http;
use InvalidArgumentException;
use IP;

/**
 * Implements the GeoLocation class, which allows to locate the user based on the IP address.
 */
class GeoLocation {
	private $config;
	private $countryCode;

	/**
	 * @param Config $config
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * Returns the country code, if the last call to self::locate() returned true. Otherwise, NULL.
	 *
	 * @return null|string
	 */
	public function getCountryCode() {
		return $this->countryCode;
	}

	/**
	 * Tries to locate the IP address set with self::setIP() using the geolocation service
	 * configured with the $wgCookieWarningGeoIPServiceURL configuration variable. If the config
	 * isn't set, this function returns NULL. If the config is set, but the URL is invalid or an
	 * other problem occures which resulted in a failed locating process, this function returns
	 * false, otherwise it returns true.
	 *
	 * @param string $ip The IP address to lookup
	 * @return bool|null NULL if no geolocation service configured, false on error, true otherwise.
	 * @throws ConfigException
	 */
	public function locate( $ip ) {
		$this->countryCode = null;
		if ( !IP::isValid( $ip ) ) {
			throw new InvalidArgumentException( "$ip is not a valid IP address." );
		}
		if ( $this->config->get( 'CookieWarningGeoIp2' ) ) {
			if ( !file_exists( $this->config->get( 'CookieWarningGeoIp2Path' ) ) ||
				!class_exists( '\GeoIp2\Database\Reader' ) ) {
				return null;
			}

			try {
				$reader = new \GeoIp2\Database\Reader( $this->config->get( 'CookieWarningGeoIp2Path' ) );
				$record = $reader->city( $ip );
				$this->countryCode = $record->country->isoCode;
			} catch ( \Exception $ex ) {
				return null;
			}
		} elseif ( $this->config->get( 'CookieWarningGeoIPServiceURL' ) ) {
			$requestUrl = $this->config->get( 'CookieWarningGeoIPServiceURL' );
			if ( substr( $requestUrl, -1 ) !== '/' ) {
				$requestUrl .= '/';
			}
			$json = Http::get( $requestUrl . $ip, [
				'timeout' => '2'
			] );
			if ( !$json ) {
				return false;
			}
			$returnObject = json_decode( $json );
			if ( $returnObject === null || !property_exists( $returnObject, 'country_code' ) ) {
				return false;
			}
			$this->countryCode = $returnObject->country_code;
		} else {
			return null;
		}

		return true;
	}
}
