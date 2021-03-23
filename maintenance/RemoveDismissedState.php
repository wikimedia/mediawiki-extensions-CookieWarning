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

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

class RemoveDismissedState extends Maintenance {
	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Remove the "cookiewarning_dismissed" upo to re-display the banner.' );

		$this->requireExtension( 'CookieWarning' );
	}

	public function execute() {
		$db = $this->getDB( DB_MASTER );
		if ( $db === null ) {
			$this->error( 'Could not get DB' );
			return;
		}

		if ( !$db->tableExists( 'user_properties', __METHOD__ ) ) {
			$this->error( 'Table "user_properties" does not exist.' );
			return;
		}

		$db->delete( 'user_properties', [
				'up_property' => 'cookiewarning_dismissed'
		] );

		$this->output( 'Removed all "cookiewarning_dismissed" settings.' );
	}
}

$maintClass = RemoveDismissedState::class;
require_once RUN_MAINTENANCE_IF_MAIN;
