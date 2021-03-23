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

namespace MediaWiki\Extension\CookieWarning\GeoLocation;

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
	public function __construct( string $geoIPServiceURL ) {
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
