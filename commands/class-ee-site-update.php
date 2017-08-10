<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Class EE_Site_Create
 */
class EE_Site_Update extends WP_CLI_Command {

	/**
	 * Update existing site.
	 *
	 * ## OPTIONS
	 *
	 * <site_name>
	 * :Site name to be created.
	 *
	 * [--html]
	 * : Update to html site.
	 *
	 * [--php]
	 * : Update site to php 5.6.
	 *
	 * [--php7]
	 * : Update site to php7.
	 *
	 * [--mysql]
	 * : Update site to php and mysql database.
	 *
	 * [--wp]
	 * : Update site to wordpress installed.
	 *
	 * [--wpfc]
	 * : Update site to wordpress + nginx fastcgi_cache.
	 *
	 * [--w3tc]
	 * : Update site to wordpress with w3-total-cache plugin.
	 *
	 * [--wpsc]
	 * : Update site to wordpress with whisp-super-cache plugin.
	 *
	 * [--wpredis]
	 * : Update site to wordpress + nginx redis_cache.
	 *
	 * [--wpsubdir]
	 * : Update wordpress site to multi site with subdirectory.
	 *
	 * [--wpsubdom]
	 * : Update wordpress site to multi site with subdomain.
	 *
	 * @when before_wp_load
	 *
	 * @param array $args     arguments for the command.
	 * @param array $ass_args associative arguments for the command.
	 */
	public function __invoke( $args, $ass_args ) {
		$db = new EE_DB();
		$db->init();
		$current_settings = $db->site_info( $args[0] );

		if ( empty( $args ) ) {
			WP_CLI::error( 'You cannot update site without sitename' );
		}
		if ( empty( $ass_args ) ) {
			WP_CLI::error( 'You cannot update site without arguments to upgrade' );
		}
		if ( ! $db->check_compatibility( $ass_args ) ) {
			WP_CLI::error( 'Some error occurred during argument checking' );
		}
		if ( ! $db->site_exists( $args[0] ) ) {
			WP_CLI::error( 'Site does not exist with domain : ' . $args[0] );
		}
		if ( WP_CLI\Utils\get_flag_value( $ass_args, 'wpsubdir' ) ) {
			$multisite = 'subdirectory';
			$site_type = 'wp';
		}
		if ( WP_CLI\Utils\get_flag_value( $ass_args, 'wpsubdom' ) ) {
			$multisite = 'subdomain';
			$site_type = 'wp';
		}

		if ( isset( $ass_args['w3tc'] ) && $ass_args['w3tc'] ) {
			$cache_type = 'total_cache';
			$site_type  = 'wp';
		}
		if ( WP_CLI\Utils\get_flag_value( $ass_args, 'wpsc' ) ) {
			$cache_type = 'super_cache';
			$site_type  = 'wp';
		}
		if ( WP_CLI\Utils\get_flag_value( $ass_args, 'wpfc' ) ) {
			$cache_type = 'fast_cgi_cache';
			$site_type  = 'wp';
		}
		if ( WP_CLI\Utils\get_flag_value( $ass_args, 'wpredis' ) ) {
			$cache_type = 'redis_cache';
			$site_type  = 'wp';
		}

		if ( ( isset( $ass_args['wp'] ) && $ass_args['wp'] ) ) {
			$site_type = 'wp';
		}

		if ( ( WP_CLI\Utils\get_flag_value( $ass_args, 'php' ) ) || ( WP_CLI\Utils\get_flag_value( $ass_args, 'php7' ) ) ) {
			$php_version = '5.6';
			if ( WP_CLI\Utils\get_flag_value( $ass_args, 'php7' ) ) {
				$php_version = '7.0';
			}
			$site_type = isset( $site_type ) && 'wp' === $site_type ? 'wp' : 'php';
		}
		if ( ( WP_CLI\Utils\get_flag_value( $ass_args, 'mysql' ) ) ) {
			$mysql = true;
		}

		if ( WP_CLI\Utils\get_flag_value( $ass_args, 'html' ) ) {
			$site_type = 'html';
		}
		if ( isset( $site_type ) && 'wp' === $site_type ) {
			$mysql = true;
		}

		if ( 'disabled' !== $current_settings['multi_site'] && $current_settings['multi_site'] !== $multisite ) {
			WP_CLI::error( 'You cannot change type of multi site.' );
		}

		if ( isset( $mysql ) && $mysql && empty( $current_settings['sql_username'] ) ) {
			// Create mysqlcreds.
			$sql_username = str_replace( '.', '_', $args[0] );
			$sql_db_name  = $sql_username;
			$sql_password = $db->randomPassword();
		}

		$site_type = isset( $site_type ) ? $site_type : 'html';

		if ( 'wp' === $current_settings['site_type'] && 'php' === $site_type ) {
			$site_type = 'wp';
		}

		$site_type    = isset( $site_type )    ? $site_type    : $current_settings['site_type'];
		$cache_type   = isset( $cache_type )   ? $cache_type   : $current_settings['cache_type'];
		$php_version  = isset( $php_version )  ? $php_version  : $current_settings['php_version'];
		$multisite    = isset( $multisite )    ? $multisite    : $current_settings['multi_site'];
		$sql_username = isset( $sql_username ) ? $sql_username : $current_settings['sql_username'];
		$sql_db_name  = isset( $sql_db_name )  ? $sql_db_name  : $current_settings['sql_db_name'];
		$sql_password = isset( $sql_password ) ? $sql_password : $current_settings['sql_password'];

		if ( $db->update_site( $args[0], $site_type, $cache_type, $php_version, $sql_username, $sql_db_name, $sql_password, $multisite ) ) {
			WP_CLI::success( 'Site updated successfully' );
		} else {
			WP_CLI::error( 'An error occured' );
		}
		$db->close();
	}
}
