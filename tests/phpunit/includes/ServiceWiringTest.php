<?php

namespace CookieWarning\Tests;

use CookieWarning\HttpGeoLocation;
use CookieWarning\NoopGeoLocation;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;

class ServiceWiringTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers \CookieWarning\NoopGeoLocation
	 */
	public function testGeoLocationWithoutServiceURL() {
		$this->overrideConfigValues( [
			'CookieWarningGeoIPServiceURL' => null
		] );

		$geoLocation = MediaWikiServices::getInstance()->getService( 'GeoLocation' );

		$this->assertInstanceOf( NoopGeoLocation::class, $geoLocation );
	}

	/**
	 * @covers \CookieWarning\HttpGeoLocation
	 */
	public function testGeoLocationWithServiceURL() {
		$this->overrideConfigValues( [
			'CookieWarningGeoIPServiceURL' => 'http://localhost/'
		] );

		$geoLocation = MediaWikiServices::getInstance()->getService( 'GeoLocation' );

		$this->assertInstanceOf( HttpGeoLocation::class, $geoLocation );
	}
}
