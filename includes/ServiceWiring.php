<?php

use CookieWarning\Decisions;
use CookieWarning\HttpGeoIP2GeoLocation;
use CookieWarning\HttpGeoLocation;
use CookieWarning\NoopGeoLocation;
use MediaWiki\MediaWikiServices;

return [
	'CookieWarning.Config' => static function ( MediaWikiServices $services ) {
		return $services->getService( 'ConfigFactory' )
			->makeConfig( 'cookiewarning' );
	},
	'GeoLocation' => static function ( MediaWikiServices $services ) {
		$geoIP2 = $services
			->getService( 'CookieWarning.Config' )
			->get( 'CookieWarningGeoIp2Path' );
		if ( is_string( $geoIP2 ) && !empty( $geoIP2 ) ) {
			return new HttpGeoIP2GeoLocation( $geoIP2 );
		}

		$geoIPServiceURL = $services
			->getService( 'CookieWarning.Config' )
			->get( 'CookieWarningGeoIPServiceURL' );

		if ( !is_string( $geoIPServiceURL ) || !$geoIPServiceURL ) {
			return new NoopGeoLocation();
		}
		return new HttpGeoLocation( $geoIPServiceURL, $services->getHttpRequestFactory() );
	},
	'CookieWarning.Decisions' => static function ( MediaWikiServices $services ) {
		return new Decisions(
			$services->getService( 'CookieWarning.Config' ),
			$services->getService( 'GeoLocation' ),
			$services->getMainWANObjectCache(),
			$services->getUserOptionsLookup()
		);
	},
];
