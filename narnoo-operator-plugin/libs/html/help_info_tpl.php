<?php

//If action is clear cache then we must clear the narnoo API cache

$action = $_REQUEST['action'];
if(!empty($action)){

	switch ($action) {
		case 'clear_cache':
			$cache = Narnoo_Operator_Helper::init_noo_cache();
			//$cache->clear();
			$cPath = $cache->getPath();
			$dir = scandir($cPath);
			foreach ($dir as $folder) {
				
				$dirname = $cPath . $folder;
					
					if( is_dir( $dirname ) ){
						
						array_map('unlink', glob("$dirname/*.txt"));
						rmdir($dirname);

					}
				
			}

			break;
	}
	

}

?><div class="wrap">
	<h2><?php _e( 'Narnoo Plugin Information', NARNOO_OPERATOR_I18N_DOMAIN ) ?> <?php echo sprintf(
									'<a href="?%s" class="button button-secondary" title="Deletes all saved Narnoo API calls">%s</a>',
									build_query(
										array(
											'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '',
											'action' => 'clear_cache'
										)
									),
									__( 'Clear Narnoo API cache', NARNOO_OPERATOR_I18N_DOMAIN )
								);?></h2>
	<hr/>
	<p>
		Some information goes in here...
	</p>
</div>