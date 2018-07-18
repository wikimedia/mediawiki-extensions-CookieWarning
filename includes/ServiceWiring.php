<?php

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
		return new CookieWarningDecisions( $services->getService( 'CookieWarning.Config' ),
			$services->getService( 'GeoLocation' ) );
	},
];
