<?php

use CookieWarning\Decisions;
use CookieWarning\GeoLocation;
use MediaWiki\MediaWikiServices;

return [
	'CookieWarning.Config' => function ( MediaWikiServices $services ) {
		return $services->getService( 'ConfigFactory' )
			->makeConfig( 'cookiewarning' );
	},
	'GeoLocation' => function ( MediaWikiServices $services ) {
		return new GeoLocation( $services->getService( 'CookieWarning.Config' ) );
	},
	'CookieWarning.Decisions' => function ( MediaWikiServices $services ) {
		return new Decisions( $services->getService( 'CookieWarning.Config' ),
			$services->getService( 'GeoLocation' ), $services->getMainWANObjectCache() );
	},
];
