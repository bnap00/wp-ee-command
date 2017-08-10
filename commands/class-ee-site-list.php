<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Class EE_Site_Create
 */
class EE_Site_List extends WP_CLI_Command {

	/**
	 * List all the site.
	 *
	 * @when before_wp_load
	 */
	public function __invoke() {
		$db = new EE_DB();
		$db->init();
		$db->site_list();
		$db->close();
	}
}
