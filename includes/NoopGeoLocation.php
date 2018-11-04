<?php

namespace CookieWarning;

class NoopGeoLocation implements GeoLocation {

	/**
	 * {@inheritdoc}
	 * @param string $ip The IP address to lookup
	 * @return bool|null NULL if no geolocation service configured, false on error, true otherwise.
	 */
	public function locate( $ip ) {
		return null;
	}
}
