<?php

namespace CookieWarning;

interface GeoLocation {
	/**
	 * Tries to locate the given IP address.
	 *
	 * @param string $ip The IP address to lookup
	 * @return null|string NULL on error or if locating the IP was not possible, the country
	 * code otherwise
	 */
	public function locate( $ip );
}
