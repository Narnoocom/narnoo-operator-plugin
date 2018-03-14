<?php

//If action is clear cache then we must clear the narnoo API cache

$action = $_REQUEST['action'];
if(!empty($action)){

	switch ($action) {
		case 'clear_cache':
			$c=0;
			$cache = Narnoo_Operator_Helper::init_noo_cache();
			$cPath = $cache->getPath();
			
			
			$dir = glob($cPath.'*');
			foreach($dir as $folder){
				if( is_dir( $folder ) ){
					$files = glob($folder."/*");
					foreach ($files as $f ) {
						unlink( $f );
						$c++;
					}
				}
			}

			if($c > 0){
				Narnoo_Operator_Helper::show_notification(
					sprintf(
						__( '<strong>Success:</strong> Your Narnoo API cache has been cleared.', NARNOO_OPERATOR_I18N_DOMAIN ),
						NARNOO_OPERATOR_I18N_DOMAIN
					)
				);
			}else{
				Narnoo_Operator_Helper::show_notification(
					sprintf(
						__( '<strong>Alert:</strong> Your Narnoo API cache was empty.', NARNOO_OPERATOR_I18N_DOMAIN ),
						NARNOO_OPERATOR_I18N_DOMAIN
					)
				);
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