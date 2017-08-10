<?php
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Class EE_Site_Create
 */
class EE_Site_Delete extends WP_CLI_Command {

	/**
	 * Delete the site.
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
			$this->_show_in_table( $info );
		}

		WP_CLI::confirm( 'Are you sure you want to delete this site?? THIS CANNOT BE UNDONE' );

		if ( $db->delete_site( $args[0] ) ) {
			WP_CLI::success( 'Site deleted successfully' );
		} else {
			WP_CLI::error( 'Something went wrong' );
		}
		$db->close();
	}
}
