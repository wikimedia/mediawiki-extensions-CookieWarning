<?php

namespace CookieWarning;

use InvalidArgumentException;
use MediaWiki\Http\HttpRequestFactory;
use Wikimedia\IPUtils;

/**
 * Implements the GeoLocation class, which allows to locate the user based on the IP address.
 */
class HttpGeoLocation implements GeoLocation {
	private string $geoIPServiceURL;
	private array $locatedIPs = [];
	private HttpRequestFactory $httpRequestFactory;

	public function __construct(
		string $geoIPServiceURL,
		HttpRequestFactory $httpRequestFactory
	) {
		if ( !$geoIPServiceURL ) {
			throw new InvalidArgumentException( 'The geoIPServiceUL is invalid' );
		}
		$this->geoIPServiceURL = $geoIPServiceURL;
		$this->httpRequestFactory = $httpRequestFactory;
	}

	/**
	 * {@inheritdoc}
	 * @param string $ip The IP address to lookup
	 * @return string|null
	 */
	public function locate( string $ip ): ?string {
		if ( isset( $this->locatedIPs[$ip] ) ) {
			return $this->locatedIPs[$ip];
		}
		if ( !IPUtils::isValid( $ip ) ) {
			throw new InvalidArgumentException( "$ip is not a valid IP address." );
		}
		if ( substr( $this->geoIPServiceURL, -1 ) !== '/' ) {
			$this->geoIPServiceURL .= '/';
		}
		$json = $this->httpRequestFactory->get( $this->geoIPServiceURL . $ip, [
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
