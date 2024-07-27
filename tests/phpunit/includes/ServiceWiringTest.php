<?php

namespace CookieWarning\Tests;

use CookieWarning\HttpGeoLocation;
use CookieWarning\NoopGeoLocation;
use MediaWikiIntegrationTestCase;

class ServiceWiringTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers \CookieWarning\NoopGeoLocation
	 */
	public function testGeoLocationWithoutServiceURL() {
		$this->overrideConfigValues( [
			'CookieWarningGeoIPServiceURL' => null
		] );

		$geoLocation = $this->getServiceContainer()->getService( 'GeoLocation' );

		$this->assertInstanceOf( NoopGeoLocation::class, $geoLocation );
	}

	/**
	 * @covers \CookieWarning\HttpGeoLocation
	 */
	public function testGeoLocationWithServiceURL() {
		$this->overrideConfigValues( [
			'CookieWarningGeoIPServiceURL' => 'http://localhost/'
		] );

		$geoLocation = $this->getServiceContainer()->getService( 'GeoLocation' );

		$this->assertInstanceOf( HttpGeoLocation::class, $geoLocation );
	}
}
