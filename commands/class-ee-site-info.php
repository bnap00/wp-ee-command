<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Class EE_Site_Create
 */
class EE_Site_Info extends WP_CLI_Command {

	/**
	 * Show information about the site.
	 *
	 * <site_name>
	 * :Site name to be created.
	 *
	 * @when before_wp_load
	 *
	 * @param array $args arguments for the command.
	 */
	public function __invoke( $args ) {
		$db = new EE_DB();
		$db->init();
		if ( ! $db->site_exists( $args[0] ) ) {
			WP_CLI::error( 'Site does not exists with domain : ' . $args[0] );
		}
		$info = $db->site_info( $args[0] );
		if ( ! empty( $info ) ) {
			$db->show_in_table( $info );
		}
		$db->close();
	}
}
