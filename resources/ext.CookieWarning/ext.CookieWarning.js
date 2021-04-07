( function ( mw ) {
	var container = document.querySelector( '.mw-cookiewarning-container' ),
		options = {};

	/**
	 * Sets the cookie, that the cookiewarning is dismissed. Called,
	 * when the api query to save this information in the user preferences,
	 * failed for any reason, or the user is not logged-in.
	 */
	function setCookie() {
		mw.cookie.set( 'cookiewarning_dismissed', true );
	}

	if ( mw.cookie.get( 'cookiewarning_dismissed' ) ) {
		container.parentNode.removeChild( container );
	} else {
		// Click handler for the "Ok" element in the cookiewarning information bar
		if ( container.querySelector( 'button' ) === null ) {
			return;
		}

		container.querySelector( 'button' ).addEventListener( 'click', function ( ev ) {
			mw.hook( 'cookiewarning.dismissed' ).fire( true );
			// an anonymous user doesn't have preferences, so don't try to save this in
			// the user preferences.
			if ( !mw.user.isAnon() ) {
				// try to save, that the cookiewarning was disabled, in the user preferences
				/* eslint-disable */
				options = {
					cookiewarning_dismissed: '1',
				}

				new mw.Api().saveOptions( options )
					.fail( function ( code, result ) {
						// if it fails, fall back to the cookie
						mw.log.warn( 'Failed to save dismissed CookieWarning: ' + code + '\n' + result.error + '. Using cookie now.' );
						setCookie();
					} );
				/* eslint-enable */
			} else {
				// use cookies for anonymous users
				setCookie();
			}
			// always remove the cookiewarning element
			container.parentNode.removeChild( container );

			ev.preventDefault();
		} );
	}
}( mediaWiki ) );
