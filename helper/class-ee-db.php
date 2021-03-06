<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$autoload = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

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
	 * Incompatibility matrix.
	 *
	 * @var string
	 */
	private $incompatibility_matrix = array();

	/**
	 * EE_DB constructor.
	 */
	function __construct() {
		$config_file_path = $this->config_location . '/' . $this->config_file;
		parent::__construct( $config_file_path );
	}

	/**
	 * Initialize the database for EE.
	 */
	public function init() {
		$this->exec( 'CREATE TABLE IF NOT EXISTS `site_data` (
			`ID`	       INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
			`site_name`    TEXT    NOT NULL UNIQUE,
			`site_type`	   TEXT    NOT NULL DEFAULT \'html\',
			`cache_type`   TEXT    NOT NULL DEFAULT \'disabled\',
			`php_version`  TEXT    NOT NULL DEFAULT \'disabled\',
			`sql_username` TEXT,
			`sql_db_name`  TEXT,
			`sql_password` TEXT,
			`multi_site`   TEXT DEFAULT \'disabled\'
			);' );

		$this->incompatibility_matrix = array(
			'html'     => array(
				'php',
				'php7',
				'mysql',
				'wp',
				'wpfc',
				'w3tc',
				'wpsc',
				'wpredis',
				'wpsubdir',
				'wpsubdom',
			),
			'php'      => array(),
			'php7'     => array(),
			'mysql'    => array(),
			'wp'       => array(),
			'wpfc'     => array(
				'wpredis',
				'w3tc',
				'wpsc',
			),
			'w3tc'     => array(
				'wpredis',
				'wpsc',
			),
			'wpsc'     => array(
				'wpredis',
			),
			'wpredis'  => array(),
			'wpsubdir' => array(
				'wpsubdom',
			),
		);
	}

	/**
	 * Check if the input associative args are compatible with each other.
	 *
	 * @param array $ass_args associative argurments to create command.
	 *
	 * @return bool will exit the function in case of incompatibility and return true to continue.
	 */
	function check_compatibility( $ass_args ) {
		if ( ! empty( $ass_args ) ) {
			foreach ( $ass_args as $key => $value ) {
				foreach ( $ass_args as $inner_key => $inner_value ) {
					if ( $key === $inner_key ) {
						continue;
					} else {
						if ( array_key_exists( $inner_key, $this->incompatibility_matrix[ $key ] ) ) {
							WP_CLI::error( "Cannot use the combination of inputs : $key and $inner_key" );
						} elseif ( array_key_exists( $key, $this->incompatibility_matrix[ $inner_key ] ) ) {
							WP_CLI::error( "Cannot use the combination of inputs : $key and $inner_key" );
						} else {
							continue;
						}
					}
				}
			}
		} else {
			WP_CLI::error( 'empty argumets to check' );
		}
		return true;
	}

	/**
	 * Insert the new site into database.
	 *
	 * @param string $site_name    Name of the site.
	 * @param string $site_type    site type. One of 'wp', 'php', 'html'.
	 * @param string $cache_type   cache type of the site. One of 'total_cache', 'super_cache', 'fast_cgi_cache',
	 *                             'redis_cache').
	 * @param string $php_version  php version of the site. One of '5.6', '7.0', 'disabled'.
	 * @param string $sql_username sql username for the site.
	 * @param string $sql_db_name  sql database name.
	 * @param string $sql_password sql password of the site.
	 * @param string $multisite    type of multi site One of the 'subdirectory', 'subdomain', 'disabled'.
	 *
	 * @return bool if the
	 */
	public function insert_site( $site_name, $site_type, $cache_type, $php_version, $sql_username, $sql_db_name, $sql_password, $multisite ) {
		$query = "INSERT INTO site_data (site_name, site_type, cache_type, php_version, sql_username, sql_db_name, sql_password, multi_site) VALUES ( '$site_name', '$site_type', '$cache_type', '$php_version', '$sql_username', '$sql_db_name', '$sql_password', '$multisite');";
		$this->exec( $query );
		if ( $this->changes() > 0 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Update site to new specifications.
	 *
	 * @param string $site_name    Name of the site.
	 * @param string $site_type    site type. One of 'wp', 'php', 'html'.
	 * @param string $cache_type   cache type of the site. One of 'total_cache', 'super_cache', 'fast_cgi_cache',
	 *                             'redis_cache').
	 * @param string $php_version  php version of the site. One of '5.6', '7.0', 'disabled'.
	 * @param string $sql_username sql username for the site.
	 * @param string $sql_db_name  sql database name.
	 * @param string $sql_password sql password of the site.
	 * @param string $multisite    type of multi site One of the 'subdirectory', 'subdomain', 'disabled'.
	 *
	 * @return bool if the
	 */
	public function update_site( $site_name, $site_type, $cache_type, $php_version, $sql_username, $sql_db_name, $sql_password, $multisite ) {
		$query = "UPDATE site_data SET `site_type` = '$site_type', `cache_type` = '$cache_type', `php_version` = '$php_version', `sql_username` = '$sql_username', `sql_db_name` = '$sql_db_name', `sql_password` = '$sql_password', `multi_site` = '$multisite' WHERE `site_name` ='$site_name'";
		$this->exec( $query );
		if ( $this->changes() > 0 ) {
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
		$query  = "SELECT COUNT(*) FROM `site_data` WHERE `site_name`='$site_name'";
		$result = $this->query( $query );
		$row    = $result->fetchArray();
		if ( 0 < $row['COUNT(*)'] ) {
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
		$result = $this->query( $query );
		if ( false !== $result ) {
			while ( $row = $result->fetchArray() ) {
				WP_CLI::log( $row['site_name'] );
			}
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
		$query  = "SELECT * FROM `site_Data` WHERE `site_name`='$site_name';";
		$result = $this->query( $query );
		$row    = $result->fetchArray( SQLITE3_INTEGER );

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
		$query = "DELETE FROM `site_data` WHERE `site_name`='$site_name';";
		$this->exec( $query );
		if ( $this->changes() > 0 ) {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * Generate random password for mysql enabled sites
	 *
	 * @param int $length (Optional) length of password.
	 *
	 * @return bool|string
	 */
	public function randomPassword( $length = 12 ) {
		$alphabets = 'abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789';
		for ( $counter = 0; $counter < $length; $counter++ ) {
			$character            = rand( 0, strlen( $alphabets ) - 1 );
			$password[ $counter ] = $alphabets[ $character ];
		}

		return isset( $password ) ? implode( '', $password ) : false;
	}

	/**
	 * Show given data in table format.
	 *
	 * @param array $data response of the query.
	 */
	public function show_in_table( $data ) {
		$result = array();
		foreach ( $data as $key => $value ) {
			if ( ! empty( $value ) && 'disabled' !== $value ) {
				array_push(
					$result,
					array(
						'key'   => $key,
						'value' => $value,
					)
				);
			}
		}
		WP_CLI\Utils\format_items(
			'table',
			$result,
			array(
				'key',
				'value',
			)
		);
	}

}
