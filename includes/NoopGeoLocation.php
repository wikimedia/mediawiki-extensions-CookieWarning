<?php

namespace CookieWarning;

class NoopGeoLocation implements GeoLocation {

	/**
	 * {@inheritdoc}
	 * @param string $ip The IP address to lookup
	 * @return string|null
	 */
	public function locate( string $ip ): ?string {
		return null;
	}
}
