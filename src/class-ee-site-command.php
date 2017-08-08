<?php
if ( ! class_exists( 'EE_Site_Command' ) && class_exists( 'EE_DB' ) ) {
	/**
	 * Easy Engine for simple site management.
	 */
	class EE_Site_Command extends WP_CLI_Command {

		/**
		 * Create site.
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
			$db -> init();

			if ( $db -> site_exists( $args[ 0 ] ) ) {
				WP_CLI ::error( 'Site Already existing with domain : ' . $args[ 0 ] );
			}
			if ( ! isset( $args[ 0 ] ) || empty( $args[ 0 ] ) ) {
				WP_CLI ::error( 'You cannot create site without sitename' );
			}
			if ( isset( $ass_args[ 'wpsubdir' ] ) && $ass_args[ 'wpsubdir' ] ) {
				if ( isset( $ass_args[ 'wpsubdom' ] ) && $ass_args[ 'wpsubdom' ] ) {
					WP_CLI ::error( 'you cannot create wp subdir site with wp subdomain site' );
				}
				if ( isset( $ass_args[ 'html' ] ) && $ass_args[ 'html' ] ) {
					WP_CLI ::error( 'you cannot create a wordpress site with html' );
				}
				$multisite = 'subdirectory';
				$site_type = 'wp';
			}
			if ( isset( $ass_args[ 'wpsubdom' ] ) && $ass_args[ 'wpsubdom' ] ) {
				if ( isset( $ass_args[ 'wpsubdir' ] ) && $ass_args[ 'wpsubdir' ] ) {
					WP_CLI ::error( 'you cannot create wp subdir site with wp subdomain site' );
				}
				if ( isset( $ass_args[ 'html' ] ) && $ass_args[ 'html' ] ) {
					WP_CLI ::error( 'you cannot create a wordpress site with html' );
				}
				$multisite = 'subdomain';
				$site_type = 'wp';
			}

			if ( isset( $ass_args[ 'w3tc' ] ) && $ass_args[ 'w3tc' ] ) {
				if ( isset( $ass_args[ 'html' ] ) && $ass_args[ 'html' ] ) {
					WP_CLI ::error( 'you cannot create a wordpress site with html' );
				}
				if ( isset( $ass_args[ 'wpsc' ] ) && $ass_args[ 'wpsc' ] ) {
					WP_CLI ::error( 'cannot combine w3tc with wpsc' );
				}
				if ( isset( $ass_args[ 'wpfc' ] ) && $ass_args[ 'wpfc' ] ) {
					WP_CLI ::error( 'cannot combine w3tc with wpfc' );
				}
				if ( isset( $ass_args[ 'wpredis' ] ) && $ass_args[ 'wpredis' ] ) {
					WP_CLI ::error( 'cannot combine w3tc with wpredis' );
				}
				$cache_type = 'total_cache';
				$site_type  = 'wp';
			}
			if ( isset( $ass_args[ 'wpsc' ] ) && $ass_args[ 'wpsc' ] ) {
				if ( isset( $ass_args[ 'html' ] ) && $ass_args[ 'html' ] ) {
					WP_CLI ::error( 'you cannot create a wordpress site with html' );
				}
				if ( isset( $ass_args[ 'wpfc' ] ) && $ass_args[ 'wpfc' ] ) {
					WP_CLI ::error( 'cannot combine wpsc with wpfc' );
				}
				if ( isset( $ass_args[ 'wpredis' ] ) && $ass_args[ 'wpredis' ] ) {
					WP_CLI ::error( 'cannot combine wpsc with wpredis' );
				}
				$cache_type = 'super_cache';
				$site_type  = 'wp';
			}
			if ( isset( $ass_args[ 'wpfc' ] ) && $ass_args[ 'wpfc' ] ) {
				if ( isset( $ass_args[ 'html' ] ) && $ass_args[ 'html' ] ) {
					WP_CLI ::error( 'you cannot create a wordpress site with html' );
				}
				if ( isset( $ass_args[ 'wpredis' ] ) && $ass_args[ 'wpredis' ] ) {
					WP_CLI ::error( 'cannot combine wpfc with wpfc' );
				}
				$cache_type = 'fast_cgi_cache';
				$site_type  = 'wp';
			}
			if ( isset( $ass_args[ 'wpredis' ] ) && $ass_args[ 'wpredis' ] ) {
				if ( isset( $ass_args[ 'html' ] ) && $ass_args[ 'html' ] ) {
					WP_CLI ::error( 'you cannot create a wordpress site with html' );
				}
				$cache_type = 'redis_cache';
				$site_type  = 'wp';
			}

			if ( ( isset( $ass_args[ 'wp' ] ) && $ass_args[ 'wp' ] ) ) {
				if ( isset( $ass_args[ 'html' ] ) && $ass_args[ 'html' ] ) {
					WP_CLI ::error( 'you cannot create a php site with html' );
				}
				$site_type = 'wp';
			}

			if ( ( isset( $ass_args[ 'php' ] ) && $ass_args[ 'php' ] ) || ( isset( $ass_args[ 'php7' ] ) && $ass_args[ 'php7' ] ) ) {
				if ( isset( $ass_args[ 'html' ] ) && $ass_args[ 'html' ] ) {
					WP_CLI ::error( 'you cannot create a php site with html' );
				}
				$php_version = 5.6;
				if ( isset( $ass_args[ 'php7' ] ) && $ass_args[ 'php7' ] ) {
					$php_version = 7;
				}
				$site_type = isset( $site_type ) && 'wp' === $site_type ? 'wp' : 'php';
			}
			if ( ( isset( $ass_args[ 'mysql' ] ) && $ass_args[ 'mysql' ] ) ) {
				if ( isset( $ass_args[ 'html' ] ) && $ass_args[ 'html' ] ) {
					WP_CLI ::error( 'you cannot create a php site with html' );
				}
				$mysql = true;
			}

			if ( isset( $ass_args[ 'html' ] ) && $ass_args[ 'html' ] ) {
				$site_type = 'html';
			}
			if ( isset( $site_type ) && 'wp' === $site_type ) {
				if ( ! isset( $php_version ) ) {
					$php_version = 5.6;
				}
				$mysql = true;
			}

			if ( isset( $mysql ) && $mysql ) {
				// Create mysqlcreds.
				$sql_username = str_replace( '.', '_', $args[ 0 ] );
				$sql_db_name  = $sql_username;
				$sql_password = $this -> _randomPassword();

			}
			$site_type    = isset( $site_type ) ? $site_type : 'html';
			$cache_type   = isset( $cache_type ) ? $cache_type : 'disabled';
			$php_version  = isset( $php_version ) ? $php_version : 'disabled';
			$multisite    = isset( $multisite ) ? $multisite : 'disabled';
			$sql_username = isset( $sql_username ) ? $sql_username : null;
			$sql_db_name  = isset( $sql_db_name ) ? $sql_db_name : null;
			$sql_password = isset( $sql_password ) ? $sql_password : null;

			$db = new EE_DB();
			$db -> init();
			if ( $db -> insert_site( $args[ 0 ], $site_type, $cache_type, $php_version, $sql_username, $sql_db_name, $sql_password, $multisite ) ) {
				WP_CLI ::success( 'Site created successfully' );
			} else {
				WP_CLI ::error( 'An error occured' );
			}
		}

		/**
		 * List all the site.
		 *
		 * @when before_wp_load
		 */
		public function list() {
			$db = new EE_DB();
			$db -> init();
			$db -> site_list();
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
			$db -> init();
			if ( ! $db -> site_exists( $args[ 0 ] ) ) {
				WP_CLI ::error( 'Site does not exists with domain : ' . $args[ 0 ] );
			}
			$info = $db -> site_info( $args[ 0 ] );
			if ( ! empty( $info ) ) {
				$this -> _show_in_table( $info );
			}
			WP_CLI ::confirm( 'Are you sure you want to delete this site?? THIS CANNOT BE UNDONE' );
			if ( $db -> delete_site( $args[ 0 ] ) ) {
				WP_CLI ::success( 'Site deleted successfully' );
			} else {
				WP_CLI ::error( 'Something went wrong' );
			}
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
			$db -> init();
			if ( ! $db -> site_exists( $args[ 0 ] ) ) {
				WP_CLI ::error( 'Site does not exists with domain : ' . $args[ 0 ] );
			}
			$info = $db -> site_info( $args[ 0 ] );
			if ( ! empty( $info ) ) {
				$this -> _show_in_table( $info );
			}
		}

		/**
		 * Generate random password for mysql enabled sites
		 *
		 * @return bool|string
		 */
		private function _randomPassword() {
			$alphabet = 'abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789';
			for ( $i = 0; $i < 12; $i ++ ) {
				$n          = rand( 0, strlen( $alphabet ) - 1 );
				$pass[ $i ] = $alphabet[ $n ];
			}

			return isset( $pass ) ? implode( '', $pass ) : false;
		}

		/**
		 * Show given data in table format.
		 *
		 * @param array $data response of the query.
		 */
		private function _show_in_table( $data ) {
			$result = array();
			foreach ( $data as $key => $value ) {
				array_push(
					$result,
					array(
						'key'   => $key,
						'value' => $value,
					)
				);
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

	WP_CLI ::add_command( 'ee site', 'EE_Site_Command' );
} // End if().
