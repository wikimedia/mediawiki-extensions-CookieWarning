<?php

namespace CookieWarning;

class NoopGeoLocation implements GeoLocation {

	/**
	 * {@inheritdoc}
	 */
	public function getCountryCode() {
		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function locate( $ip ) {
		return null;
	}
}
