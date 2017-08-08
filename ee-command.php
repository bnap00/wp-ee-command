<?php
use \WP_CLI\Utils;
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$autoload = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
}
WP_CLI::add_command( 'ee site', 'EE_Site_Command' );