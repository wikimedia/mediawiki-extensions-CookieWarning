<?php

namespace CookieWarning;

use Http;
use InvalidArgumentException;
use IP;

/**
 * Implements the GeoLocation class, which allows to locate the user based on the IP address.
 */
class HttpBackedGeoLocation implements GeoLocation {
	private $geoIPServiceURL;
	private $countryCode;

	/**
	 * @param MediaWiki\MediaWikiServices $geoIPServiceURL
	 */
	public function __construct( $geoIPServiceURL ) {
		if ( !is_string( $geoIPServiceURL ) || !$geoIPServiceURL ) {
			throw new InvalidArgumentException( 'The geoIPServiceUL is invalid' );
		}
		$this->geoIPServiceURL = $geoIPServiceURL;
	}

	/**
	 * {@inheritdoc}
	 * @return null|string
	 */
	public function getCountryCode() {
		return $this->countryCode;
	}

	/**
	 * {@inheritdoc}
	 * @param string $ip The IP address to lookup
	 * @return bool|null NULL if no geolocation service configured, false on error, true otherwise.
	 */
	public function locate( $ip ) {
		$this->countryCode = null;
		if ( !IP::isValid( $ip ) ) {
			throw new InvalidArgumentException( "$ip is not a valid IP address." );
		}
		if ( substr( $this->geoIPServiceURL, -1 ) !== '/' ) {
			$this->geoIPServiceURL .= '/';
		}
		$json = Http::get( $this->geoIPServiceURL . $ip, [
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
		return true;
	}
}
