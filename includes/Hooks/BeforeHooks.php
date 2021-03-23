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

use ConfigException;
use MediaWiki;
use MediaWiki\Extension\CookieWarning\CookieWarning;
use MediaWiki\Extension\CookieWarning\Decisions;
use MediaWiki\Hook\BeforeInitializeHook;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\MediaWikiServices;
use MWException;
use OutputPage;
use Title;
use User;
use WebRequest;

class BeforeHooks implements BeforeInitializeHook, BeforePageDisplayHook {

	/**
	 * BeforeInitialize hook handler.
	 *
	 * If the disablecookiewarning POST data is send, disables the cookiewarning bar with a
	 * cookie or a user preference, if the user is logged in.
	 *
	 * @param Title $title
	 * @param null $unused
	 * @param OutputPage $output
	 * @param User $user
	 * @param WebRequest $request
	 * @param MediaWiki $mediawiki
	 * @throws MWException
	 */
	public function onBeforeInitialize( $title, $unused, $output, $user, $request, $mediawiki ) {
		$config = CookieWarning::getConfig();

		if ( $config !== null && $config->get( 'CookieWarningUnsetCookies' ) === true ) {
			$this->unsetCookies( $user, $request );
		}

		if ( !$request->wasPosted() || !$request->getVal( 'disablecookiewarning' ) ) {
			return;
		}

		$manager = MediaWikiServices::getInstance()->getUserOptionsManager();

		if ( $user->isRegistered() ) {
			$manager->setOption( $user, 'cookiewarning_dismissed', 1 );
			$manager->setOption(
				$user,
				'cookiewarning_cookies_preference',
				$request->getVal( 'cookiewarning_cookies_preference', false ) === true
			);
			$manager->setOption(
				$user,
				'cookiewarning_cookies_statistic',
				$request->getVal( 'cookiewarning_cookies_statistic', false ) === true
			);
			$manager->setOption(
				$user,
				'cookiewarning_cookies_marketing',
				$request->getVal( 'cookiewarning_cookies_marketing', false ) === true
			);
			$manager->setOption( $user, 'cookiewarning_cookies_required', true );

			$user->saveSettings();
		} else {
			$request->response()->setCookie( 'cookiewarning_dismissed', true );
			$request->response()->setCookie( 'cookiewarning_preferences', json_encode( [
				'required' => true,
				'preference' => false,
				'statistic' => false,
				'marketing' => false,
			] ) );
		}

		$output->redirect( $request->getRequestURL() );
	}

	/**
	 * BeforePageDisplay hook handler.
	 *
	 * Adds the required style and JS module, if cookiewarning is enabled.
	 *
	 * @param OutputPage $out
	 * @throws ConfigException
	 * @throws MWException
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$config = CookieWarning::getConfig();

		if ( $config !== null && $config->get( 'CookieWarningUnsetCookies' ) === true ) {
			$this->unsetCookies( $skin->getUser(), $skin->getRequest() );
		}

		/** @var Decisions $cookieWarningDecisions */
		$cookieWarningDecisions = MediaWikiServices::getInstance()
			->getService( 'CookieWarning.Decisions' );

		if ( !$cookieWarningDecisions->shouldShowCookieWarning( $out->getContext() ) ) {
			return;
		}

		$modules = [ 'ext.CookieWarning' ];
		$moduleStyles = [ 'ext.CookieWarning.styles' ];

		if ( $cookieWarningDecisions->shouldAddResourceLoaderComponents() ) {
			$modules[] = 'ext.CookieWarning.geolocation';
			$moduleStyles[] = 'ext.CookieWarning.geolocation.styles';
		}

		$out->addModules( $modules );
		$out->addModuleStyles( $moduleStyles );
		$out->enableOOUI();
	}

	/**
	 * Unsets _all_ cookies that are not defined in CookieWarningRequiredCookies
	 * IF the 'cookiewarning_dismissed' cookie was not found
	 * OR the user has NO 'cookiewarning_dismissed' option set
	 *
	 * We currently have
	 *
	 * @param User $user
	 * @param WebRequest $request
	 */
	private function unsetCookies( User $user, WebRequest $request ): void {
		$cookies = $_COOKIE;

		foreach ( $cookies as $name => $cookie ) {
			if ( !CookieWarning::isAllowedCookie( $name, $request, $user ) ) {
				setcookie( $name, $cookie, 1 );
				unset( $_COOKIE[$name] );
			}
		}
	}
}
