( function ( mw, $ ) {
	$( '.mw-cookiewarning-dismiss' ).on( 'click', function ( ev ) {
		mw.cookie.set( 'cookiewarning_dismissed', true );
		$( '.mw-cookiewarning-container' ).detach();
		ev.preventDefault();
	} );
} )( mediaWiki, jQuery );
