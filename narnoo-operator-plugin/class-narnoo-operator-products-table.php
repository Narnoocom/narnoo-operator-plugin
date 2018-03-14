<?php
/**
 * Narnoo Operator - Products table.
 **/
class Narnoo_Operator_Products_Table extends WP_List_Table {
	
	function column_default( $item, $column_name ) {
		switch( $column_name ) { 
			case 'title':
			case 'product_id':
			case 'entry_date':
			case 'modified_date':
			
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}

	function column_title( $item ) {    
		$actions = array(
			
			'Import'    	=> sprintf( 
									'<a href="?%s">%s</a>', 
									build_query( 
										array(
											'page' 		 => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '',
											'paged' 	 => $this->get_pagenum(),
											'action' 	 => 'import', 
											'products[]' => $item['product_id'], 
										)
									),
									__( 'Import', NARNOO_OPERATOR_I18N_DOMAIN ) 
								),
		);
		return sprintf( 
			'<input type="hidden" name="url%1$s" value="%2$s" /> %3$s <br /> %4$s', 
			$item['product_id'],
			$item['title'],
			"<span class='row-title'>".$item['title']."</span>", 
			$this->row_actions($actions) 
		);
	}
	
	function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="products[]" value="%s" />', $item['product_id']
		);    
	}

	function get_columns() {
		return array(
			'cb'				=> '<input type="checkbox" />',
			//'thumbnail_image'	=> __( 'Thumbnail', 	NARNOO_OPERATOR_I18N_DOMAIN ),
			'title'				=> __( 'Title', 		NARNOO_OPERATOR_I18N_DOMAIN ),
			'product_id'		=> __( 'Product ID', 	NARNOO_OPERATOR_I18N_DOMAIN ),
			//'summary'			=> __( 'Summary', 		NARNOO_OPERATOR_I18N_DOMAIN ),
			'entry_date'		=> __( 'Date Created', 	NARNOO_OPERATOR_I18N_DOMAIN ),
			'modified_date'		=> __( 'Date Modified', NARNOO_OPERATOR_I18N_DOMAIN )
		);
	}


	function get_bulk_actions() {
		$actions = array(
			'Import'		=> __( 'Import', NARNOO_OPERATOR_I18N_DOMAIN )
		);
		return $actions;
	}	

	/**
	 * Process actions and returns true if the rest of the table SHOULD be rendered.
	 * Returns false otherwise.
	 **/
	function process_action() {

		if ( isset( $_REQUEST['cancel'] ) ) {
			Narnoo_Operator_Helper::show_notification( __( 'Action cancelled.', NARNOO_OPERATOR_I18N_DOMAIN ) );
			return true;
		}
		
		if ( isset( $_REQUEST['back'] ) ) {
			return true;
		}
		
		$action = $this->current_action();



		if ( false !== $action ) {

			$product_ids = isset( $_REQUEST['products'] ) ? $_REQUEST['products'] : array();
			$num_ids = count( $product_ids );
			
			if ( empty( $product_ids ) || ! is_array( $product_ids ) || $num_ids === 0 ) {
				return true;				
			}

			if ( $action === 'import' ) { 
				
					$action = 'do_import';
			}
			
			switch ( $action ) {
				// perform import
				case 'import':					
					?>

					<h3><?php _e( 'Import Product', NARNOO_OPERATOR_I18N_DOMAIN ); ?></h3>
					<p><?php echo sprintf( __( "Requesting import information for the following %s product(s):", NARNOO_OPERATOR_I18N_DOMAIN ), $num_ids ); ?></p>
					<ol>
					<?php
					foreach( $product_ids as $id ) {
						Narnoo_Operator_Helper::print_ajax_script_body( $id, 'getProductDetails', array( $id ) );
					}
					?>
					</ol>
					<?php 
						Narnoo_Operator_Helper::print_ajax_script_footer( $num_ids, __( 'Back to products', NARNOO_OPERATOR_I18N_DOMAIN ) );

					return false;

					// perform actual import
				case 'do_import':					
					?>
					<h3><?php _e( 'Import',  NARNOO_OPERATOR_I18N_DOMAIN ); ?></h3>
					<p><?php echo sprintf( __( "Requesting import information from Narnoo for the following %s product(s):", NARNOO_OPERATOR_I18N_DOMAIN ), $num_ids ); ?></p>
					<ol>
					<?php
					foreach( $product_ids as $id ) {
						Narnoo_Operator_Helper::print_ajax_script_body( 
							$id, 
							'getProductDetails', 
							array( $id ), 
							'ID #' . $id , 'self', true
						);
					}
					?> 
					</ol>
					<?php 
					Narnoo_Operator_Helper::print_ajax_script_footer( $num_ids, __( 'Back to products', NARNOO_OPERATOR_I18N_DOMAIN ) );

					return false;
					
			} 	// end switch( $action )
		}	// endif ( false !== $action )*/
		
		return true;
	}
	
	/**
	 * Request the current page data from Narnoo API server.
	 **/
	function get_current_page_data() {
		$data = array( 'total_pages' => 1, 'items' => array() );
		
		$list 			= null;
		$current_page 	= $this->get_pagenum();
		$cache	 		= Narnoo_Operator_Helper::init_noo_cache();
		$request 		= Narnoo_Operator_Helper::init_api();

		if ( ! is_null( $request ) ) {
	

				$list = $cache->get('products_'.$current_page);

				if( empty($list) ){

					try {
						
						$list = $request->getProducts( $current_page );

						if ( ! is_array( $list->product ) ) {
							throw new Exception( sprintf( __( "Error retrieving products. Unexpected format in response page #%d.", NARNOO_OPERATOR_I18N_DOMAIN ), $current_page ) );
						}

						if(!empty( $list->success ) ){
							$cache->set('products_'.$current_page, $list, 21600);
						}
						

					} catch ( Exception $ex ) {
						Narnoo_Operator_Helper::show_api_error( $ex );
					} 


				}



		}
		
		if ( ! is_null( $list ) ) {
			$data['total_pages'] = max( 1, intval( 1 ) );//check this..
			foreach ( $list->product as $product ) {
				//$item['thumbnail_image'] 	= $product->feature_image->crop_image_path;
				$item['title'] 				= $product->product_title;
				//$item['summary'] 			= $product->summary->text;
				$item['entry_date'] 		= $product->date_created;
				$item['modified_date'] 		= $product->date_modified; // update spelling in the API
				$item['product_id'] 		= $product->product_id;
				$data['items'][] 			= $item;
			}
		}
		//print_r($data);
		return $data;
	}
	
	/**
	 * Process any actions (displaying forms for the actions as well).
	 * If the table SHOULD be rendered after processing (or no processing occurs), prepares the data for display and returns true. 
	 * Otherwise, returns false.
	 **/
	function prepare_items() {		
		if ( ! $this->process_action() ) {
			return false;
		}		

		$this->_column_headers = $this->get_column_info();
		
		$data = $this->get_current_page_data();
		$this->items = $data['items'];
		
		$this->set_pagination_args( array(
			'total_items'	=> count( $data['items'] ),
			'total_pages'	=> $data['total_pages']
		) );  

		return true;
	}
	
	/**
	 * Add screen options for products page.
	 **/
	static function add_screen_options() {
		global $narnoo_operator_products_table;
		$narnoo_operator_products_table = new Narnoo_Operator_Products_Table();
	}
}    