<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

declare( strict_types=1 );

namespace MediaWiki\Extension\CookieWarning\Hooks;

use HTMLForm;
use MediaWiki\Extension\CookieWarning\CookieWarning;
use MediaWiki\MediaWikiServices;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\Preferences\Hook\PreferencesFormPreSaveHook;
use User;

/**
 * Hooks to run relating to user preferences
 */
class PreferenceHooks implements PreferencesFormPreSaveHook, GetPreferencesHook {

	/**
	 * Hardcodes the 'required' value and runs the CookiePreferencesChanged hook
	 *
	 * @param array $formData Array of user submitted data
	 * @param HTMLForm $form HTMLForm object, also a ContextSource
	 * @param User $user User with preferences to be saved
	 * @param bool &$result Boolean indicating success
	 * @param array $oldUserOptions Array with user's old options (before save)
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onPreferencesFormPreSave( $formData, $form, $user, &$result, $oldUserOptions ) {
		if ( !isset( $formData['cookiewarning_cookies_required'] ) ) {
			return;
		}

		// We hardcode the 'required' cookie value
		MediaWikiServices::getInstance()
			->getUserOptionsManager()
			->setOption( $user, 'cookiewarning_cookies_required', true );

		$data = [
			'required' => true,
			'preference' => ( $formData['cookiewarning_cookies_preference'] ?? false ) === true,
			'statistic' => ( $formData['cookiewarning_cookies_statistic'] ?? false ) === true,
			'marketing' => ( $formData['cookiewarning_cookies_marketing'] ?? false ) === true,
		];

		MediaWikiServices::getInstance()->getHookContainer()->run( 'CookiePreferencesChanged', $data );
		$form->getRequest()->response()->setCookie( 'cookiewarning_preferences', json_encode( $data ) );
	}

	/**
	 * Add three api settable options
	 *
	 * Displays the accepted cookie groups to the user and makes them changeable
	 *
	 * @param User $user User whose preferences are being modified.
	 * @param array[] &$preferences Preferences description array, to be fed to a HTMLForm object.
	 */
	public function onGetPreferences( $user, &$preferences ) {
		$preferences['cookiewarning_dismissed'] = [
			'type' => 'api',
			'default' => '0',
		];
		$preferences['cookiewarning_cookies_preference'] = [
			'type' => 'api',
			'default' => false,
		];
		$preferences['cookiewarning_cookies_statistic'] = [
			'type' => 'api',
			'default' => false,
		];
		$preferences['cookiewarning_cookies_marketing'] = [
			'type' => 'api',
			'default' => false,
		];

		if ( CookieWarning::getConfig() === null ||
			CookieWarning::getConfig()->get( 'CookieWarningEnablePreferences' ) === false ) {
			return;
		}

		$manager = MediaWikiServices::getInstance()->getUserOptionsManager();

		$cookieSettings = [
			'required' => true,
			'preference' => $manager->getBoolOption( $user, 'cookiewarning_cookies_preference' ),
			'statistic' => $manager->getBoolOption( $user, 'cookiewarning_cookies_statistic' ),
			'marketing' => $manager->getBoolOption( $user, 'cookiewarning_cookies_marketing' ),
		];

		$cookieSettings = array_filter( $cookieSettings, static function ( $pref ) {
			return $pref === true;
		} );

		// Preferences to add.
		$cookieWarningPrefs = [
			'cookiewarning_cookies_' => [
				'type' => 'multiselect',
				'label-message' => 'prefs-cookiewarning-label',
				'section' => 'personal/info',
				'options' => [
					wfMessage( 'cookiewarning-required-cookies-label' )->escaped() => 'required',
					wfMessage( 'cookiewarning-preference-cookies-label' )->escaped() => 'preference',
					wfMessage( 'cookiewarning-statistic-cookies-label' )->escaped() => 'statistic',
					wfMessage( 'cookiewarning-marketing-cookies-label' )->escaped() => 'marketing',

				],
				'disabled-options' => [
					'required'
				],
				'default' => array_keys( $cookieSettings )
			],
		];

		$preferences += $cookieWarningPrefs;
	}
}
