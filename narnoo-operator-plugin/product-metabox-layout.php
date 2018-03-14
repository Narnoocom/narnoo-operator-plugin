<?php
/*
 * Product pages metaboxes
 */

/**
*
*	Show a checkbox for featured products
*
*/

add_action( 'cmb2_admin_init', 'narnoo_product_feature' );
function narnoo_product_feature() {

    // Start with an underscore to hide fields from custom fields list
    $prefix = 'narnoo_';

    /**
     * Initiate the metabox
     */
    $cmb = new_cmb2_box( array(
        'id'            => 'featured_narnoo_product',
        'title'         => __( 'Feature This Product', NARNOO_OPERATOR_I18N_DOMAIN ),
        'object_types'  => array( 'narnoo_product' ), // Post type
        'context'       => 'side',
        'priority'      => 'low',
        'show_names'    => true,
    ) );

    $cmb->add_field( array(
    'name' => 'Set as featured product',
    'desc' => 'Check to set product (optional)',
    'id'   => $prefix.'featured_product',
    'type' => 'checkbox',
	) );

}

//Returns a number for he excerpt
function the_excerpt_max_charlength($charlength) {
	$excerpt = get_the_excerpt();
	$charlength++;

	if ( mb_strlen( $excerpt ) > $charlength ) {
		$subex = mb_substr( $excerpt, 0, $charlength - 5 );
		$exwords = explode( ' ', $subex );
		$excut = - ( mb_strlen( $exwords[ count( $exwords ) - 1 ] ) );
		if ( $excut < 0 ) {
			echo mb_substr( $subex, 0, $excut );
		} else {
			echo $subex;
		}
		echo '[...]';
	} else {
		echo $excerpt;
	}
}

/**
*
*
*	We only want to show this metabox if the product is an attraction
*	This is stored in the get_option() database value.
* 	
*
*/
$isAttraction = get_option('narnoo_operator_category');
if(!empty($isAttraction) && $isAttraction == 'attraction'){
	add_filter( 'cmb2_init', 'narnoo_product_attraction_metaboxes' );
}else{
	add_filter( 'cmb2_init', 'narnoo_product_metaboxes' );
}

/**
*
*
*	This shows the information required for the attractions product type.
* 	
*
*/
function narnoo_product_metaboxes() {
	$box_options = array(
		'id'           => 'product_tabs_metaboxes',
		'title'        => __( 'Product Information', NARNOO_OPERATOR_I18N_DOMAIN ),
		'object_types' => array( 'narnoo_product' ),
		'show_names'   => true,
	);

	// Setup meta box
	$cmb = new_cmb2_box( $box_options );
	// Setting tabs
	$tabs_setting           = array(
		'config' => $box_options,
		'layout' => 'vertical', // Default : horizontal
		'tabs'   => array()
	);

	//Manage each of the tabs as complete collection of fields
	$tabs_setting['tabs'][] = array(
		'id'     => 'tab1',
		'title'  => __( 'Details', NARNOO_OPERATOR_I18N_DOMAIN ),
		'fields' => array(
			array(
				'name' => __( 'Narnoo ID', NARNOO_OPERATOR_I18N_DOMAIN ),
				'id'   => 'narnoo_product_id',
				'type' => 'text_medium',
				'save_field' => false, // Disables the saving of this field.
				'attributes' => array(
					'disabled' => 'disabled',
					'readonly' => 'readonly',
				),
			),
			array(
				'name' => __( 'Priced From', NARNOO_OPERATOR_I18N_DOMAIN ),
				'id'   => 'product_min_price',
				'type' => 'text_money'
			)
		)
	);
	
	$tabs_setting['tabs'][] = array(
		'id'     => 'tab2',
		'title'  => __( 'Purchases', NARNOO_OPERATOR_I18N_DOMAIN ),
		'fields' => array(
			array(
				'name' => __( 'Purchase Details', NARNOO_OPERATOR_I18N_DOMAIN ),
				'id'   => 'narnoo_product_purchase',
				'type'    => 'wysiwyg',
				'options' => array(
					'textarea_rows' => 50,
				),
			)
		)
	);

	$tabs_setting['tabs'][] = array(
		'id'     => 'tab3',
		'title'  => __( 'Additional Information', NARNOO_OPERATOR_I18N_DOMAIN ),
		'fields' => array(
			array(
				'name' => __( 'Details', NARNOO_OPERATOR_I18N_DOMAIN ),
				'id'   => 'narnoo_product_additional',
				'type'    => 'wysiwyg',
				'options' => array(
					'textarea_rows' => 50,
				),
			)
		)
	);

	$tabs_setting['tabs'][] = array(
		'id'     => 'tab4',
		'title'  => __( 'Booking Link', NARNOO_OPERATOR_I18N_DOMAIN ),
		'fields' => array(
			array(
				'name' => __( 'Booking Link', NARNOO_OPERATOR_I18N_DOMAIN ),
				'id'   => 'product_booking_link',
				'type' => 'text'
			)
			
		)
	);

	// Set tabs
	$cmb->add_field( array(
		'id'   => '__tabs',
		'type' => 'tabs',
		'tabs' => $tabs_setting
	) );
}

/**
*
*
*	This shows the information required for the attractions product type.
* 	
*
*/
function narnoo_product_attraction_metaboxes() {
	$box_options = array(
		'id'           => 'product_tabs_metaboxes',
		'title'        => __( 'Product Information', NARNOO_OPERATOR_I18N_DOMAIN ),
		'object_types' => array( 'narnoo_product' ),
		'show_names'   => true,
	);

	// Setup meta box
	$cmb = new_cmb2_box( $box_options );
	// Setting tabs
	$tabs_setting           = array(
		'config' => $box_options,
		'layout' => 'vertical', // Default : horizontal
		'tabs'   => array()
	);

	//Manage each of the tabs as complete collection of fields
	$tabs_setting['tabs'][] = array(
		'id'     => 'tab1',
		'title'  => __( 'Details', NARNOO_OPERATOR_I18N_DOMAIN ),
		'fields' => array(
			array(
				'name' => __( 'Narnoo ID', NARNOO_OPERATOR_I18N_DOMAIN ),
				'id'   => 'narnoo_product_id',
				'type' => 'text_medium',
				'save_field' => false, // Disables the saving of this field.
				'attributes' => array(
					'disabled' => 'disabled',
					'readonly' => 'readonly',
				),
			),
			array(
				'name' => __( 'Priced From', NARNOO_OPERATOR_I18N_DOMAIN ),
				'id'   => 'product_min_price',
				'type' => 'text_money'
			),
			array(
				'name' => __( 'Duration (hrs)', NARNOO_OPERATOR_I18N_DOMAIN ),
				'id'   => 'narnoo_product_duration',
				'type' => 'text_small'
			),
			array(
				'name' => __( 'Start Time', NARNOO_OPERATOR_I18N_DOMAIN ),
				'id'   => 'narnoo_product_start_time',
				'type' => 'text_time'
			),
			array(
				'name' => __( 'End Time', NARNOO_OPERATOR_I18N_DOMAIN ),
				'id'   => 'narnoo_product_end_time',
				'type' => 'text_time'
			)
		)
	);
	$tabs_setting['tabs'][] = array(
		'id'     => 'tab2',
		'title'  => __( 'Transport', NARNOO_OPERATOR_I18N_DOMAIN ),
		'fields' => array(
			array(
				'name' => __( 'Transport Details', NARNOO_OPERATOR_I18N_DOMAIN ),
				'id'   => 'narnoo_product_transport',
				'type'    => 'wysiwyg',
				'options' => array(
					'textarea_rows' => 50,
				),
			)
		)
	);
	$tabs_setting['tabs'][] = array(
		'id'     => 'tab3',
		'title'  => __( 'Purchases', NARNOO_OPERATOR_I18N_DOMAIN ),
		'fields' => array(
			array(
				'name' => __( 'Purchase Details', NARNOO_OPERATOR_I18N_DOMAIN ),
				'id'   => 'narnoo_product_purchase',
				'type'    => 'wysiwyg',
				'options' => array(
					'textarea_rows' => 50,
				),
			)
		)
	);

	$tabs_setting['tabs'][] = array(
		'id'     => 'tab4',
		'title'  => __( 'Packing', NARNOO_OPERATOR_I18N_DOMAIN ),
		'fields' => array(
			array(
				'name' => __( 'What to pack', NARNOO_OPERATOR_I18N_DOMAIN ),
				'id'   => 'narnoo_product_packing',
				'type'    => 'wysiwyg',
				'options' => array(
					'textarea_rows' => 50,
				),
			)
		)
	);

	$tabs_setting['tabs'][] = array(
		'id'     => 'tab5',
		'title'  => __( 'Health', NARNOO_OPERATOR_I18N_DOMAIN ),
		'fields' => array(
			array(
				'name' => __( 'Health Details', NARNOO_OPERATOR_I18N_DOMAIN ),
				'id'   => 'narnoo_product_health',
				'type'    => 'wysiwyg',
				'options' => array(
					'textarea_rows' => 50,
				),
			)
		)
	);

	$tabs_setting['tabs'][] = array(
		'id'     => 'tab6',
		'title'  => __( 'Children', NARNOO_OPERATOR_I18N_DOMAIN ),
		'fields' => array(
			array(
				'name' => __( 'Children Policy', NARNOO_OPERATOR_I18N_DOMAIN ),
				'id'   => 'narnoo_product_children',
				'type'    => 'wysiwyg',
				'options' => array(
					'textarea_rows' => 50,
				),
			)
		)
	);

	$tabs_setting['tabs'][] = array(
		'id'     => 'tab7',
		'title'  => __( 'Itinerary', NARNOO_OPERATOR_I18N_DOMAIN ),
		'fields' => array(
			array(
				'name' => __( 'Itinerary Details', NARNOO_OPERATOR_I18N_DOMAIN ),
				'id'   => 'product_itinerary',
				'type'    => 'wysiwyg',
				'options' => array(
					'textarea_rows' => 50,
				),
			)
		)
	);

	$tabs_setting['tabs'][] = array(
		'id'     => 'tab8',
		'title'  => __( 'Additional Information', NARNOO_OPERATOR_I18N_DOMAIN ),
		'fields' => array(
			array(
				'name' => __( 'Details', NARNOO_OPERATOR_I18N_DOMAIN ),
				'id'   => 'narnoo_product_additional',
				'type'    => 'wysiwyg',
				'options' => array(
					'textarea_rows' => 50,
				),
			)
		)
	);

	$tabs_setting['tabs'][] = array(
		'id'     => 'tab9',
		'title'  => __( 'Booking Link', NARNOO_OPERATOR_I18N_DOMAIN ),
		'fields' => array(
			array(
				'name' => __( 'Booking Link', NARNOO_OPERATOR_I18N_DOMAIN ),
				'id'   => 'product_booking_link',
				'type' => 'text'
			)
			
		)
	);

	// Set tabs
	$cmb->add_field( array(
		'id'   => '__tabs',
		'type' => 'tabs',
		'tabs' => $tabs_setting
	) );
}