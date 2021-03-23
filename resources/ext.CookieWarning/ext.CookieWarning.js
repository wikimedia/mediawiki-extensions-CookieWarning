( function ( mw ) {
	var container = document.querySelector( '.mw-cookiewarning-container' ),
		inputPreference = document.querySelector( '#cookiewarning-cookies-preference input' ),
		inputStatistic = document.querySelector( '#cookiewarning-cookies-statistic input' ),
		inputMarketing = document.querySelector( '#cookiewarning-cookies-marketing input' );

	/**
	 * Sets the cookie, that the cookiewarning is dismissed. Called,
	 * when the api query to save this information in the user preferences,
	 * failed for any reason, or the user is not logged-in.
	 */
	function setCookie() {
		var preferenceData = {
			required: true,
			preference: typeof inputPreference.checked === 'undefined' ? false : inputPreference.checked,
			statistic: typeof inputStatistic.checked === 'undefined' ? false : inputStatistic.checked,
			marketing: typeof inputMarketing.checked === 'undefined' ? false : inputMarketing.checked
		};

		mw.hook( 'cookiewarning.preferences_set' ).fire( preferenceData );

		mw.cookie.set( 'cookiewarning_dismissed', true );
		mw.cookie.set( 'cookiewarning_preferences', JSON.stringify( preferenceData ) );
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
				new mw.Api().saveOptions( {
					'cookiewarning_dismissed': '1',
					'cookiewarning_cookies_preference': typeof inputPreference.checked === 'undefined' ? false : inputPreference.checked,
					'cookiewarning_cookies_statistics': typeof inputStatistic.checked === 'undefined' ? false : inputStatistic.checked,
					'cookiewarning_cookies_marketing': typeof inputMarketing.checked === 'undefined' ? false : inputMarketing.checked
				} )
					.fail( function ( code, result ) {
						// if it fails, fall back to the cookie
						mw.log.warn( 'Failed to save dismissed CookieWarning: ' + code + '\n' + result.error + '. Using cookie now.' );
						setCookie();
					} );
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
