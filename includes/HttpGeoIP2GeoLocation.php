<?php

namespace CookieWarning;

use GeoIp2\Database\Reader;
use InvalidArgumentException;
use Throwable;
use Wikimedia\IPUtils;

/**
 * Implements the GeoIP2GeoLocation class, which allows to locate the user based on the IP address using geoip2.
 */
class HttpGeoIP2GeoLocation implements GeoLocation {
	private $geoIP2;
	private $locatedIPs = [];

	/**
	 * @param string $geoIP2
	 */
	public function __construct( $geoIP2 ) {
		$this->geoIP2 = $geoIP2;
	}

	/**
	 * {@inheritdoc}
	 * @param string $ip The IP address to lookup
	 * @return string|null
	 */
	public function locate( $ip ) {
		if ( !class_exists( 'Reader' ) ) {
			throw new InvalidArgumentException( 'You must install the geoip2 composer package.' );
		} elseif ( !file_exists( $geoIP2 ) ) {
			throw new InvalidArgumentException( 'File specified in geoIP2 does not exist' );
		}

		if ( isset( $this->locatedIPs[$ip] ) ) {
			return $this->locatedIPs[$ip];
		}

		if ( !IPUtils::isValid( $ip ) ) {
			throw new InvalidArgumentException( "$ip is not a valid IP address." );
		}

		try {
			$reader = new Reader( $this->geoIP2 );
			$record = $reader->city( $ip );
			$this->locatedIPs[$ip] = $record->country->isoCode;

			return $this->locatedIPs[$ip];
		} catch ( Throwable $ex ) {
			return null;
		}
	}
}
