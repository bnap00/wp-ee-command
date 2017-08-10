<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Class EE_Site_Create
 */
class EE_Site_Create extends WP_CLI_Command {

	/**
	 * Create new site.
	 *
	 * ## OPTIONS
	 *
	 * <site_name>
	 * :Site name to be created.
	 *
	 * [--html]
	 * : Create html site.
	 *
	 * [--php]
	 * : Create site with php 5.6.
	 *
	 * [--php7]
	 * : Create site with php7.
	 *
	 * [--mysql]
	 * : Create site with php and mysql database.
	 *
	 * [--wp]
	 * : Create site with wordpress pre installed.
	 *
	 * [--wpfc]
	 * : Create site with wordpress + nginx fastcgi_cache.
	 *
	 * [--w3tc]
	 * : Create site with wordpress with w3-total-cache plugin.
	 *
	 * [--wpsc]
	 * : Create site with wordpress with whisp-super-cache plugin.
	 *
	 * [--wpredis]
	 * : Create site with wordpress + nginx redis_cache.
	 *
	 * [--wpsubdir]
	 * : Create a wordpress multi site with subdirectory.
	 *
	 * [--wpsubdom]
	 * : Create a wordpress multi site with subdomain.
	 *
	 * @when before_wp_load
	 *
	 * @param array $args     arguments for the command.
	 * @param array $ass_args associative arguments for the command.
	 */
	public function __invoke( $args, $ass_args ) {
		$db = new EE_DB();
		$db->init();

		if ( empty( $args[0] ) ) {
			WP_CLI::error( 'You cannot create site without sitename' );
		}
		if ( $db->site_exists( $args[0] ) ) {
			WP_CLI::error( 'Site Already existing with domain : ' . $args[0] );
		}
		if ( empty( $ass_args ) ) {
			$ass_args['html'] = true;   // Creates html site by default if nothing specified.
		}
		if ( ! $db->check_compatibility( $ass_args ) ) {
			WP_CLI::error( 'Something went wrong while checking compatibility' );
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
			$site_type = 'wp' === $site_type ? 'wp' : 'php';
		}
		if ( ( WP_CLI\Utils\get_flag_value( $ass_args, 'mysql' ) ) ) {
			$mysql = true;
		}

		if ( WP_CLI\Utils\get_flag_value( $ass_args, 'html' ) ) {
			$site_type = 'html';
		}
		if ( 'wp' === $site_type ) {
			if ( ! isset( $php_version ) ) {
				$php_version = '5.6';
			}
			$mysql = true;
		}

		if ( isset( $mysql ) && $mysql ) {
			// Create mysqlcreds.
			$sql_username = str_replace( '.', '_', $args[0] );
			$sql_db_name  = $sql_username;
			$sql_password = $db->randomPassword();

		}
		$site_type    = isset( $site_type ) ? $site_type : 'html';
		$cache_type   = isset( $cache_type ) ? $cache_type : 'disabled';
		$php_version  = isset( $php_version ) ? $php_version : 'disabled';
		$multisite    = isset( $multisite ) ? $multisite : 'disabled';
		$sql_username = isset( $sql_username ) ? $sql_username : null;
		$sql_db_name  = isset( $sql_db_name ) ? $sql_db_name : null;
		$sql_password = isset( $sql_password ) ? $sql_password : null;

		$db = new EE_DB();
		$db->init();
		if ( $db->insert_site( $args[0], $site_type, $cache_type, $php_version, $sql_username, $sql_db_name, $sql_password, $multisite ) ) {
			WP_CLI::success( 'Site created successfully' );
		} else {
			WP_CLI::error( 'An error occured' );
		}
		$db->close();
	}
}
