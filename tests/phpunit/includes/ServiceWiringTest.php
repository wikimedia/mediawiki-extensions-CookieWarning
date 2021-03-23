<?php

declare(strict_types=1);

namespace CookieWarning\Tests;

use \MediaWiki\Extension\CookieWarning\GeoLocation\HttpGeoLocation;
use \MediaWiki\Extension\CookieWarning\GeoLocation\NoopGeoLocation;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;

class ServiceWiringTest extends MediaWikiTestCase {

	/**
	 * @covers \MediaWiki\Extension\CookieWarning\GeoLocation\NoopGeoLocation
	 */
	public function testGeoLocationWithoutServiceURL() {
		$this->setMwGlobals( [
			'wgCookieWarningGeoIPServiceURL' => null
		] );

		$geoLocation = MediaWikiServices::getInstance()->getService( 'GeoLocation' );

		$this->assertInstanceOf( NoopGeoLocation::class, $geoLocation );
	}

	/**
	 * @covers \MediaWiki\Extension\CookieWarning\GeoLocation\HttpGeoLocation
	 */
	public function testGeoLocationWithServiceURL() {
		$this->setMwGlobals( [
			'wgCookieWarningGeoIPServiceURL' => 'http://localhost/'
		] );

		$geoLocation = MediaWikiServices::getInstance()->getService( 'GeoLocation' );

		$this->assertInstanceOf( HttpGeoLocation::class, $geoLocation );
	}
}
