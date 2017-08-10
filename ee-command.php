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
		if ( $this->changes( $query ) > 0 ) {
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
		if ( $this->changes( $query ) > 0 ) {
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
		$query  = "SELECT * FROM `site_Data` WHERE `site_name`='. $site_name';";
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
		if ( $this->changes( $query ) > 0 ) {
			return true;
		} else {
			return false;
		}
	}

}

if ( ! class_exists( 'EE_Site_Command' ) && class_exists( 'EE_DB' ) ) {
	/**
	 * Easy Engine for simple site management.
	 */
	class EE_Site_Command extends WP_CLI_Command {

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
		public function create( $args, $ass_args ) {
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
				$sql_password = $this->_randomPassword();

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
		public function update( $args, $ass_args ) {
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
				$sql_password = $this->_randomPassword();
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

		/**
		 * List all the site.
		 *
		 * @when before_wp_load
		 */
		public function list() {
			$db = new EE_DB();
			$db->init();
			$db->site_list();
			$db->close();
		}

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
		public function delete( $args ) {
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
		public function info( $args ) {
			$db = new EE_DB();
			$db->init();
			if ( ! $db->site_exists( $args[0] ) ) {
				WP_CLI::error( 'Site does not exists with domain : ' . $args[0] );
			}
			$info = $db->site_info( $args[0] );
			if ( ! empty( $info ) ) {
				$this->_show_in_table( $info );
			}
			$db->close();
		}

		/**
		 * Generate random password for mysql enabled sites
		 *
		 * @param int $length (Optional) length of password.
		 *
		 * @return bool|string
		 */
		private function _randomPassword( $length = 12 ) {
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
		private function _show_in_table( $data ) {
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
} // End if().
WP_CLI::add_command( 'ee site', 'EE_Site_Command' );
