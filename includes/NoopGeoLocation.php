<?php

namespace CookieWarning;

class NoopGeoLocation implements GeoLocation {

	/**
	 * {@inheritdoc}
	 * @return null|string
	 */
	public function getCountryCode() {
		return null;
	}

	/**
	 * {@inheritdoc}
	 * @param string $ip The IP address to lookup
	 * @return bool|null NULL if no geolocation service configured, false on error, true otherwise.
	 */
	public function locate( $ip ) {
		return null;
	}
}
