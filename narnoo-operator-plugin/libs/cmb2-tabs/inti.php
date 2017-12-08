<?php

namespace cmb2_tabs;

if ( is_admin() ) {
	// Run autoloader
	include NARNOO_OPERATOR_PLUGIN_PATH . 'libs/cmb2-tabs/inc/assets.class.php';
	include NARNOO_OPERATOR_PLUGIN_PATH . 'libs/cmb2-tabs/inc/cmb2-tabs.class.php';

	// Connection css and js
	new inc\Assets();

	// Run global class
	new inc\CMB2_Tabs();
}
