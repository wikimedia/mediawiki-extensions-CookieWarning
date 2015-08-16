( function ( mw, $ ) {
	function setCookie() {
		mw.cookie.set( 'cookiewarning_dismissed', true );
	}

	$( '.mw-cookiewarning-dismiss' ).on( 'click', function ( ev ) {
		if ( !mw.user.isAnon() ) {
			new mw.Api().postWithToken( 'options', {
				action: 'options',
				change: 'cookiewarning_dismissed=1'
			} ).fail( function ( code, result ) {
				mw.log.warn( 'Failed to save dismissed CookieWarning: ' + code + '\n' + result.error + '. Using cookie now.' );
				setCookie();
			} );
		} else {
			setCookie();
		}
		$( '.mw-cookiewarning-container' ).detach();
		ev.preventDefault();
	} );
} )( mediaWiki, jQuery );
