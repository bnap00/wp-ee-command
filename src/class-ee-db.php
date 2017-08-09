<?php
if ( class_exists( 'EE_DB' ) ) {
	/**
	 * Class EE_DB
	 *
	 * Database wrapper fot SQLite 3.
	 */
	class EE_DB extends SQLite3 {

		/**
		 * Location of the database file.
		 *
		 * @var string
		 */
		private $config_location = '/usr/local/var';
		/**
		 * File name of the Sqlite Database.
		 *
		 * @var string
		 */
		private $config_file = 'data.db';

		/**
		 * EE_DB constructor.
		 */
		function __construct() {
			$config_file_path = $this -> config_location . '/' . $this -> config_file;
			parent ::__construct( $config_file_path );
		}

		/**
		 * Initialize the database for EE.
		 */
		public function init() {
			$this -> exec( 'CREATE TABLE IF NOT EXISTS `site_data` (
			`site_name`	    TEXT NOT NULL UNIQUE,
			`site_type`	    TEXT NOT NULL DEFAULT \'html\',
			`cache_type`	TEXT NOT NULL DEFAULT \'disabled\',
			`php_version`	TEXT NOT NULL DEFAULT \'disabled\',
			`sql_username`	TEXT,
			`sql_db_name`	TEXT,
			`sql_password`	TEXT,
			`multi_site`	TEXT DEFAULT \'disabled\',
			PRIMARY KEY(`site_name`)
			);' );
		}

		/**
		 * Insert the new site into database.
		 *
		 * @param string $site_name    Name of the site.
		 * @param string $site_type    site type. One of 'wp', 'php', 'html'.
		 * @param string $cache_type   cache type of the site. One of 'total_cache', 'super_cache', 'fast_cgi_cache', 'redis_cache').
		 * @param string $php_version  php version of the site. One of '5.6', '7', 'disabled'.
		 * @param string $sql_username sql username for the site.
		 * @param string $sql_db_name  sql database name.
		 * @param string $sql_password sql password of the site.
		 * @param string $multisite    type of multi site One of the 'subdirectory', 'subdomain', 'disabled'.
		 *
		 * @return bool if the
		 */
		public function insert_site( $site_name, $site_type, $cache_type, $php_version, $sql_username, $sql_db_name, $sql_password, $multisite ) {
			$query = 'INSERT INTO site_data (site_name, site_type, cache_type, php_version, sql_username, sql_db_name, sql_password, multi_site) VALUES ( \'' . $site_name . '\', \'' . $site_type . '\', \'' . $cache_type . '\', \'' . $php_version . '\', \'' . $sql_username . '\', \'' . $sql_db_name . '\', \'' . $sql_password . '\', \'' . $multisite . '\');';
			WP_CLI ::warning( $query );
			if ( $this -> query( $query ) ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Check if the site exists.
		 *
		 * @param string $site_name name of the site.
		 *
		 * @return bool
		 */
		public function site_exists( $site_name ) {
			$query  = 'SELECT COUNT(*) FROM `site_data` WHERE `site_name`=\'' . $site_name . '\'';
			$result = $this -> query( $query );
			$row    = $result -> fetchArray();
			if ( 1 <= $row[ 'COUNT(*)' ] ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Find the list of sites.
		 */
		public function site_list() {
			$query  = 'SELECT `site_name` FROM `site_Data`';
			$result = $this -> query( $query );
			while ( $row = $result -> fetchArray() ) {
				WP_CLI ::log( $row[ 'site_name' ] );
			}
		}

		/**
		 * Return site information.
		 *
		 * @param string $site_name name of the site.
		 *
		 * @return array
		 */
		public function site_info( $site_name ) {
			$query  = 'SELECT * FROM `site_Data` WHERE `site_name`=\'' . $site_name . '\';';
			$result = $this -> query( $query );
			$row    = $result -> fetchArray( SQLITE3_INTEGER );

			return $row;
		}

		/**
		 * Delete the site.
		 *
		 * @param string $site_name name of the site.
		 *
		 * @return bool
		 */
		public function delete_site( $site_name ) {
			$query = 'DELETE FROM `site_data` WHERE `site_name`=\'' . $site_name . '\';';
			if ( $this -> exec( $query ) ) {
				return true;
			} else {
				return false;
			}
		}

	}
}
