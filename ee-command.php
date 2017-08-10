<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$autoload = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

require_once 'helper/class-ee-db.php';
require_once 'commands/class-ee-site-create.php';
require_once 'commands/class-ee-site-info.php';
require_once 'commands/class-ee-site-list.php';
require_once 'commands/class-ee-site-delete.php';
require_once 'commands/class-ee-site-update.php';

WP_CLI::add_command( 'ee site create', 'EE_Site_Create' );
WP_CLI::add_command( 'ee site update', 'EE_Site_Update' );
WP_CLI::add_command( 'ee site list', 'EE_Site_List' );
WP_CLI::add_command( 'ee site delete', 'EE_Site_Delete' );
WP_CLI::add_command( 'ee site info', 'EE_Site_Info' );
