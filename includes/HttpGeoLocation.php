<?php

namespace CookieWarning;

use Http;
use InvalidArgumentException;
use Wikimedia\IPUtils;

/**
 * Implements the GeoLocation class, which allows to locate the user based on the IP address.
 */
class HttpGeoLocation implements GeoLocation {
	private $geoIPServiceURL;
	private $locatedIPs = [];

	/**
	 * @param string $geoIPServiceURL
	 */
	public function __construct( $geoIPServiceURL ) {
		if ( !is_string( $geoIPServiceURL ) || !$geoIPServiceURL ) {
			throw new InvalidArgumentException( 'The geoIPServiceUL is invalid' );
		}
		$this->geoIPServiceURL = $geoIPServiceURL;
	}

	/**
	 * {@inheritdoc}
	 * @param string $ip The IP address to lookup
	 * @return string|null
	 */
	public function locate( $ip ) {
		if ( isset( $this->locatedIPs[$ip] ) ) {
			return $this->locatedIPs[$ip];
		}
		if ( !IPUtils::isValid( $ip ) ) {
			throw new InvalidArgumentException( "$ip is not a valid IP address." );
		}
		if ( substr( $this->geoIPServiceURL, -1 ) !== '/' ) {
			$this->geoIPServiceURL .= '/';
		}
		$json = Http::get( $this->geoIPServiceURL . $ip, [
			'timeout' => '2',
		] );
		if ( !$json ) {
			return null;
		}
		$returnObject = json_decode( $json );
		if ( $returnObject === null || !property_exists( $returnObject, 'country_code' ) ) {
			return null;
		}
		$this->locatedIPs[$ip] = $returnObject->country_code;

		return $this->locatedIPs[$ip];
	}
}
