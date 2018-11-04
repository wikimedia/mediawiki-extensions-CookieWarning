<?php

namespace CookieWarning;

interface GeoLocation {
	/**
	 * Returns the country code, if the last call to self::locate() returned true. Otherwise, NULL.
	 *
	 * @return null|string
	 */
	public function getCountryCode();

	/**
	 * Tries to locate the given IP address.
	 *
	 * @param string $ip The IP address to lookup
	 * @return bool|null NULL if no geolocation service configured, false on error, true otherwise.
	 */
	public function locate( $ip );
}
