<?php
/**
 * Helper functions used throughout plugin.
 **/
class Narnoo_Operator_Helper {
	/**
	 * Returns true if current Wordpress version supports wp_enqueue_script in HTML body (3.3 and above); false otherwise.
	 **/
	static function wp_supports_enqueue_script_in_body() {
		global $wp_version;
		$version = explode( '.', $wp_version );
		if ( intval( $version[0] < 3 ) || ( intval( $version[0] ) == 3 && intval( $version[1] ) < 3 ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Show generic notification message.
	 **/
	static function show_notification( $msg ) {
		echo '<div class="updated"><p>' . $msg . '</p></div>';
	}

	/**
	 * Show generic error message.
	 **/
	static function show_error( $msg ) {
		echo '<div class="error"><p>' . $msg . '</p></div>';
	}

	/**
	 * In case of API error (e.g. invalid API keys), display error message.
	 **/
	static function show_api_error( $ex, $prefix_msg = '' ) {
		$error_msg = $ex->getMessage();
		$msg = '<strong>' . __( 'Narnoo API error:', NARNOO_OPERATOR_I18N_DOMAIN ) . '</strong> ' . $prefix_msg . ' ' . $error_msg;
		if ( false !== strchr( strtolower( $error_msg ), ' authentication fail' ) ) {
			$msg .= '<br />' . sprintf(
				__( 'Please ensure your API settings in the <strong><a href="%1$s">Settings->Narnoo API</a></strong> page are correct and try again.', NARNOO_OPERATOR_I18N_DOMAIN ),
				NARNOO_OPERATOR_SETTINGS_PAGE
			);
		}
		self::show_error( $msg );
	}

	/**
	 * Inits and returns operator request object with user's access and secret keys.
	 * If either app or secret key is empty, returns null.
	 * @date_modified: 28.09.2017
	 * @change_log: Added authentication via token.
	 *   			Split out the token from authentication keys
	 **/
	static function init_api() {
		$options  = get_option( 'narnoo_operator_settings' );
		/**
		*
		*	Store keys in a different setting option
		*
		*/
		$_token   = get_option( 'narnoo_operator_token' );

		if ( empty( $options['access_key'] ) || empty( $options['secret_key'] ) ) {
			return null;
		}
		/**
		*
		*	Check to see if we have access keys and a token.
		*
		*/
		if( !empty( $options['access_key'] ) && !empty( $options['secret_key'] ) && empty($_token) ){
			/**
			*
			*	Call the Narnoo authentication to return our access token
			*
			*/
			$requestToken = new Narnooauthen( $options['access_key'],$options['secret_key'] );
			$token 		  =  $requestToken->authenticate();
			if(!empty($token)){
				/**
				*
				*	Update Narnoo access token
				*
				*/
				update_option( 'narnoo_operator_token', $token, 'yes' );
				
			}else{
				return null;
			}


		}
		/**
		*	Create authentication Header to access the API.
		**/
		$api_settings = array(
			"API-KEY: ".$options['access_key'],
			"API-SECRET-KEY: ".$options['secret_key'],
			"Authorization: ".$_token
		);

		$request = new Operator( $api_settings );

		return $request;
	}

	/*
	*
	* Inits our PHP FastCache options
	*
	*/

	static function init_noo_cache(){

		$config = array(
	        "path"      =>  NARNOO_OPERATOR_PLUGIN_PATH . "libs/cache",
	    );
 		$cache = phpFastCache("files",$config);
	    

	    return $cache;
	}


	/**
     * Retrieves list of operator product IDs that have been imported into Wordpress database.
     * */
    static function get_imported_product_ids() {
        
       $imported_ids = array();
       $imported_posts = get_posts(array('post_type' => 'narnoo_product','numberposts' => -1));
        
       foreach ($imported_posts as $post) {
            $id = get_post_meta($post->ID, 'narnoo_product_id', true);
            if (!empty($id)) {
                $imported_ids[] = $id;
            }

    	}
        return $imported_ids;
    }

     /**
     * Retrieves Wordpress post ID for imported product ID, if it exists.
     * Returns false if no such product exists in Wordpress DB.
     * */
    static function get_post_id_for_imported_product_id($product_id) {
       		
            $imported_posts = get_posts(array('post_type' => 'narnoo_product','numberposts' => -1));
            foreach ($imported_posts as $post) {
                $id = get_post_meta($post->ID, 'narnoo_product_id', true);
                if ($id === $product_id) {
                    return $post->ID;
                }
        	}

        return false;
    }



	/**
	 * Prints out the HTML/Javascript for a single item that will be processed via AJAX.
	 **/
	static function print_ajax_script_body( $id, $func_name, $params_array, $text = '', $func_type = '', $is_import_operators = false ) {
		?>
		<li>
			<img id="narnoo-icon-process-<?php echo $id; ?>" src="<?php echo admin_url(); ?>images/wpspin_light.gif" />
			<img style="display:none;" id="narnoo-icon-success-<?php echo $id; ?>" src="<?php echo admin_url(); ?>images/yes.png" />
			<img style="display:none;" id="narnoo-icon-fail-<?php echo $id; ?>" src="<?php echo admin_url(); ?>images/no.png" />
			<span><?php echo __( 'Item ID:', NARNOO_OPERATOR_I18N_DOMAIN ) . ' ' . $id . '...'; ?></span>
			<strong><span id="narnoo-item-<?php echo $id; ?>"><?php _e( 'Processing...', NARNOO_OPERATOR_I18N_DOMAIN ); ?></span></strong>
		</li>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: { action: 'narnoo_operator_api_request',
					func_name: '<?php echo $func_name; ?>',
					param_array: [ <?php echo "'" . implode( "','", $params_array ) . "'"; ?> ] },
					timeout: 60000,
					dataType: "json",
					success:
					function(response, textStatus, jqXHR) {
						$('#narnoo-icon-process-<?php echo $id; ?>').hide();
						processed++;

						if (response['success'] === 'success' && response['msg']) {
							$('#narnoo-icon-success-<?php echo $id; ?>').show();
							$('#narnoo-item-<?php echo $id; ?>').html(response['msg']);
							success++;
						} else {
							$('#narnoo-icon-fail-<?php echo $id; ?>').show();
							$('#narnoo-item-<?php echo $id; ?>').html('<?php _e( 'AJAX error: Unexpected response', NARNOO_OPERATOR_I18N_DOMAIN ); ?>');
						}

						check_complete($);
					},
					error:
					function(jqXHR, textStatus, errorThrown) {
						$('#narnoo-icon-process-<?php echo $id; ?>').hide();
						$('#narnoo-icon-fail-<?php echo $id; ?>').show();
						processed++;

							if (textStatus === 'timeout') {   // server timeout
								$('#narnoo-item-<?php echo $id; ?>').html('<?php _e( 'AJAX error: Server timeout', NARNOO_OPERATOR_I18N_DOMAIN ); ?>');
							} else {                  // other error
								$('#narnoo-item-<?php echo $id; ?>').html(jqXHR.responseText);
							}

							check_complete($);
						}
					});
});
</script>
<?php
}

	/**
	 * Prints out the footer HTML/Javascript needed for AJAX processing.
	 **/
	static function print_ajax_script_footer( $total_count, $back_button_text, $extra_button_text = '' ) {
		?>
		<div class="narnoo-completed" style="display:none;">
			<br />
			<p><strong><?php echo sprintf( __( "Processing completed. %s of %d item(s) successful.", NARNOO_OPERATOR_I18N_DOMAIN ), '<span id="narnoo-success-count"></span>', $total_count ); ?></strong></p>
		</div>
		<p class="submit narnoo-completed" style="display:none;">
			<?php
			if ( ! empty( $extra_button_text ) ) {
				?><input type="submit" name="extra_button" id="extra_button" class="button-secondary" value="<?php echo $extra_button_text; ?>" /><?php
			}
			?>
			<input type="submit" name="back" id="cancel" class="button-secondary" value="<?php echo $back_button_text; ?>" />
		</p>
		<script type="text/javascript">
			var success = 0;
			var processed = 0;
			function check_complete($) {
				if (processed >= <?php echo $total_count; ?>) {
					$('#narnoo-success-count').text(success);
					$('.narnoo-completed').show();
				}
			}
		</script>
		<?php
	}

	/**
	 * Returns HTML and Javascript required for selection of album page/album names, and querying of
	 * album pages via AJAX.
	 **/
	static function get_album_select_html_script( $albums, $total_pages, $current_album_page, $current_album_name ) {
		ob_start();
		?>
		<input type="hidden" id="narnoo_album_name" name="narnoo_album_name" value="<?php echo esc_attr( $current_album_name ); ?>" />
		<select name="narnoo_album_page" id="narnoo-album-page-select">
		<?php
		for ( $i = 0; $i < $total_pages; $i++ ) {
			$selected = '';
			if ( ( $i + 1 ) === $current_album_page ) {
				$selected = 'selected="selected"';
			}
			?><option value="<?php echo $i + 1; ?>"<?php echo $selected;?>><?php printf( __( 'Album page %d', NARNOO_OPERATOR_I18N_DOMAIN ), ($i + 1) ); ?></option><?php
		}
		?>
		</select>

		<?php
		// prepare "select album" element for every page
		for ( $i = 0; $i < $total_pages; $i++ ) {
			$is_current_page = ( $current_album_page === ( $i + 1 ) );

			$hidden = ' data-loaded="yes"';
			$disabled = '';
			if ( ! $is_current_page ) {
				$hidden = ' data-loaded="no" style="display:none;"';
				$disabled = ' disabled="disabled"';
			}
			?>
			<span class="narnoo-album-select-span" id="narnoo-album-select-span-<?php echo $i + 1; ?>"<?php echo $hidden; ?>>
				<span class="narnoo-album-select-span-process" style="display:none;">
					<img class="narnoo-icon-process" src="<?php echo admin_url(); ?>images/wpspin_light.gif" />
					<img class="narnoo-icon-fail" src="<?php echo admin_url(); ?>images/no.png" />
					<span class="narnoo-album-select-msg"></span>
				</span>
				<select class="narnoo-album-select" name="narnoo_album_id"<?php echo $disabled; ?>>
				<?php
				foreach ( $albums as $album ) {
					$album_name = stripslashes( $album->album_name );
					$selected = '';
					if ( $current_album_name === $album_name ) {
						$selected = ' selected="selected"';
					}
					?><option value="<?php echo $album->album_id; ?>"<?php echo $selected; ?>><?php echo esc_html( $album_name ); ?></option><?php
				}
				?>
				</select>
			</span>
		<?php
		}
		?>
		<script type="text/javascript">
			function updateQueryStringParameter(uri, key, value) {
				var re = new RegExp("([?|&])" + key + "=.*?(&|$)", "i");
				separator = uri.indexOf('?') !== -1 ? "&" : "?";
				value = encodeURIComponent(value);
				if (uri.match(re)) {
					return uri.replace(re, '$1' + key + "=" + value + '$2');
				}
				else {
					return uri + separator + key + "=" + value;
				}
			}

			jQuery('document').ready(function($) {
				$('#album_select_button').click(function(e, ui) {
					page = $('#narnoo-album-page-select').val();
					$selected = $('#narnoo-album-select-span-' + page).find('.narnoo-album-select option:selected');
					$('#narnoo_album_name').val($selected.html());

					// rebuild form action query string to ensure album name, id and page are in sync
					$form = $('#narnoo-albums-form');
					if ($form.length > 0) {
						new_query = $form.attr('action');
						new_query = updateQueryStringParameter(new_query, 'album', $selected.val());
						new_query = updateQueryStringParameter(new_query, 'album_name', $selected.html() );
						new_query = updateQueryStringParameter(new_query, 'album_page', page);
						$form.attr('action', new_query);
					}
				});

				$('#narnoo-album-page-select').change(function(e, ui) {
					var $this = $(this);
					var page = $(this).val();

					$('.narnoo-album-select').attr("disabled", "disabled").hide();
					$('.narnoo-album-select-span').hide();

					var $album_select_span = $('#narnoo-album-select-span-' + page);
					var $album_select = $album_select_span.find('.narnoo-album-select');
					var $album_select_span_process = $album_select_span.find('.narnoo-album-select-span-process');
					var $album_select_icon_fail = $album_select_span.find('.narnoo-icon-fail');
					var $album_select_icon_process = $album_select_span.find('.narnoo-icon-process');
					var $album_select_msg = $album_select_span.find('.narnoo-album-select-msg');

					if ($album_select_span.data('loaded') === 'yes') {
						$album_select_span.find('.narnoo-album-select').removeAttr("disabled").show();
						$("#album_select_button").removeAttr('disabled');
					} else {
						$album_select_span_process.show();
						$("#album_select_button").attr('disabled', 'disabled');

						if ($album_select_span.data('loaded') === 'no') {
							$album_select_span.data("loaded", "loading");
							$album_select_icon_fail.hide();
							$album_select_icon_process.show();
							$album_select_msg.html("<?php _e( 'Retrieving album names...', NARNOO_OPERATOR_I18N_DOMAIN ); ?>");

							// request album names via AJAX from server
							$.ajax({
								type: 'POST',
								url: ajaxurl,
								data: { action: 'narnoo_operator_api_request',
										func_name: 'getAlbums',
										param_array: [ page ] },
								timeout: 60000,
								dataType: "json",
								success:
									function(response, textStatus, jqXHR) {
										$album_select_icon_process.hide();

										error_msg = "<?php _e( 'AJAX error: Unexpected response', NARNOO_OPERATOR_I18N_DOMAIN ); ?>";
										if (response['success'] === 'success' && response['msg'] && response['response'] && response['response']['operator_albums']) {
											items = response['response']['operator_albums'];
											if (items.length === 0) {
												error_msg = "<?php _e( 'No albums found!' ); ?>";
											} else {
												// populate the select element with album names
												options = '';
												for (index in items) {
													item = items[index];
													options += '<option value="' + item['album_id'] + '">' + item['album_name'] + '</option>';
												}
												$album_select.html(options);

												$album_select_msg.html('');
												$album_select_span.data("loaded", "yes");
												if (page === $this.val()) {	// ensure the current page is still selected
													$album_select.removeAttr('disabled').show();
													$("#album_select_button").removeAttr('disabled');
												}
												return;
											}
										}

										$album_select_icon_fail.show();
										$album_select_span.data("loaded", "no");
										$album_select_msg.html(error_msg);
									},
								error:
									function(jqXHR, textStatus, errorThrown) {
										$album_select_icon_process.hide();
										$album_select_icon_fail.show();
										$album_select_span.data("loaded", "no");

										if (textStatus === 'timeout') {   // server timeout
											$album_select_msg.html('<?php _e( 'AJAX error: Server timeout', NARNOO_OPERATOR_I18N_DOMAIN ); ?>');
										} else {                  // other error
											$album_select_msg.html(jqXHR.responseText);
										}
									}
							});
						}
					}

					$album_select_span.show();
				});
			});
		</script>
		<?php

		return ob_get_clean();
	}

	/**
	 * Handling of AJAX request fatal error.
	 **/
	static function ajax_fatal_error( $sErrorMessage = '' ) {
		header( $_SERVER['SERVER_PROTOCOL'] .' 500 Internal Server Error' );
		die( $sErrorMessage );
	}

	/**
	 * Handling of empty content.
	 **/
	function empty_content($str) {
    	return trim(str_replace('&nbsp;','',strip_tags($str))) == '';
	}

	/**
	 * Handling of AJAX API requests.
	 **/
	static function ajax_api_request() {
		if ( ! isset( $_POST['func_name'] ) || ! isset( $_POST['param_array'] ) ) {
			self::ajax_fatal_error( __( 'AJAX error: Missing arguments.', NARNOO_OPERATOR_I18N_DOMAIN ) );
		}
		$func_name = $_POST['func_name'];
		$param_array = $_POST['param_array'];

		// init the API request object
		$request = Narnoo_Operator_Helper::init_api();
		if ( is_null( $request ) ) {
			self::ajax_fatal_error( __( 'Narnoo API error: Incorrect API keys specified.', NARNOO_OPERATOR_I18N_DOMAIN ) );
		}

		// attempt to call API function with specified params
		$response = array();
		try {
			$response['response'] = call_user_func_array( array( $request, $func_name), $param_array );
			if ( false === $response['response'] ) {
				self::ajax_fatal_error( __( 'AJAX error: Invalid function or arguments specified.', NARNOO_OPERATOR_I18N_DOMAIN ) );
			}
			$response['success'] = 'success';
			//print_r($response);
			
			// set success message depending on API function called
			$response['msg'] = __( 'Success!', NARNOO_OPERATOR_I18N_DOMAIN );
			$item 			 = $response['response'];

			// vvvv below here is is processing after we have stored some information vvvvv //

			if ( ! is_null( $item ) ) {
				if ( isset( $item->success ) ) {
					// copy success message directly from API response
					$response['msg'] = 'success';
				}
				if ( 'download_brochure' === $func_name ) {
					$response['msg'] .= ' <a target="_blank" href="' . $item->download_brochure_file . '">' . __( 'Download PDF brochure', NARNOO_OPERATOR_I18N_DOMAIN ) . '</a>';
				} else if ( 'download_image' === $func_name ) {
					$response['msg'] .= ' <a target="_blank" href="' . $item->download_image_file . '">' . __( 'Download image link', NARNOO_OPERATOR_I18N_DOMAIN ) . '</a>';
				} else if ( 'downloadVideo' === $func_name ) {
					$item->download_video_stream_path = uncdata( $item->download_video_stream_path );
					$item->original_video_path = uncdata( $item->original_video_path );
					$response['msg'] .= ' <a target="_blank" href="' . $item->download_video_stream_path . '">' . __( 'Download video stream path', NARNOO_OPERATOR_I18N_DOMAIN ) . '</a>';
					$response['msg'] .= ' <a target="_blank" href="' . $item->original_video_path . '">' . __( 'Original video path', NARNOO_OPERATOR_I18N_DOMAIN ) . '</a>';
				} else if ( 'getAlbums' === $func_name ) {
					// ensure each album name has slashes stripped
					$albums = $item->operator_albums;
					if ( is_array( $albums ) ) {
						foreach ( $albums as $album ) {
							$album->album_name = stripslashes( $album->album_name );
						}
					}
				} else if ('getProductDetails' === $func_name) {


					$post_id = Narnoo_Operator_Helper::get_post_id_for_imported_product_id( $item->product_id );

					if (!empty( $post_id )) {

						// update existing post, ensuring parent is correctly set
			            $update_post_fields = array(
			                'ID' 		  	=> $post_id,
			                'post_title'  	=> $item->product_title,
			                'post_type'   	=> 'narnoo_product',
			                'post_status' 	=> 'publish',
			                'post_author' 	=> $user_ID,
			                'post_modified' => date('Y-m-d H:i:s')
			            );
			            wp_update_post($update_post_fields);

			            
			            update_post_meta( $post_id, 'product_description', $item->description->english->text);
			            update_post_meta( $post_id, 'product_excerpt', 	strip_tags( $item->summary->english->text ));

			           // set a feature image for this post but first check to see if a feature is present

						$feature = get_the_post_thumbnail($post_id);
						if(empty($feature)){

							if( !empty( $item->feature_image->xxlarge_image_path ) ){
				        	$url = "https:" . $item->feature_image->xxlarge_image_path;
				        	$desc = $item->product_title . " feature image";
							$feature_image = media_sideload_image($url, $post_id, $desc);
							if(!empty($feature_image)){
			                    global $wpdb;
			                    $attachment     = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $feature_image )); 
			                    set_post_thumbnail( $post_id, $attachment[0] );
			                }
				        }

						}
				        

			            $response['msg'] = "Successfully re-imported product details";

					}else{
					
						//create new post with operator details
			            $new_post_fields = array(
			                'post_title' 		=> $item->product_title,
			                'post_excerpt'		=> strip_tags( $item->summary->english->text ),
			                'post_content' 		=> $item->description->english->text,
			                'post_status' 		=> 'publish',
			                'post_date' 		=> date('Y-m-d H:i:s'),
			                'post_author' 		=> $user_ID,
			                'post_type' 		=> 'narnoo_product',
			                'comment_status' 	=> 'closed',
			                'ping_status' 		=> 'closed'
			            );

			            $post_id = wp_insert_post($new_post_fields);
			            
			            // set a feature image for this post
				        if( !empty( $item->feature_image->xxlarge_image_path ) ){
				        	$url = "https:" . $item->feature_image->xxlarge_image_path;
				        	$desc = $item->product_title . " feature image";
							$feature_image = media_sideload_image($url, $post_id, $desc);
							if(!empty($feature_image)){
			                    global $wpdb;
			                    $attachment     = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $feature_image )); 
			                    set_post_thumbnail( $post_id, $attachment[0] );
			                }
				        }
				        
				        $response['msg'] = "Successfully imported product details";

				    }

				    // insert/update custom fields with operator details into post
			        update_post_meta($post_id, 'narnoo_product_id', 	$item->product_id);
			        update_post_meta($post_id, 'product_min_price', 	$item->min_price);
			        update_post_meta($post_id, 'product_avg_price', 	$item->avg_price);
			        update_post_meta($post_id, 'product_max_price', 	$item->max_price);
			        update_post_meta($post_id, 'product_booking_link', 	$item->direct_booking);


			        $isAttraction = get_option('narnoo_operator_category');
					if(!empty($isAttraction) && $isAttraction == 'attraction'){

						update_post_meta($post_id, 'narnoo_product_duration', 	$item->details->operating_hours);
				        update_post_meta($post_id, 'narnoo_product_start_time', $item->details->start_time);
				        update_post_meta($post_id, 'narnoo_product_end_time', 	$item->details->end_time);
				        update_post_meta($post_id, 'narnoo_product_transport', 	$item->details->pickup_departure);
				        update_post_meta($post_id, 'narnoo_product_purchase', 	$item->details->purchase_options);
				        update_post_meta($post_id, 'narnoo_product_health', 	$item->details->health_requirements);
				        update_post_meta($post_id, 'narnoo_product_packing', 	$item->details->what_to_bring);
				        update_post_meta($post_id, 'narnoo_product_children', 	$item->details->children_information);
				        update_post_meta($post_id, 'narnoo_product_additional',	$item->details->additional_information);
						
					}
					/**
					*
					*	Import the gallery images as JSON encoded object
					*
					*/
					if(!empty($item->gallery)){
						update_post_meta($post_id, 'narnoo_product_gallery', json_encode($item->gallery) );
					}else{
						delete_post_meta($post_id, 'narnoo_product_gallery');
					}
					/**
					*
					*	Import the video player object
					*
					*/
					if(!empty($item->feature_video)){
						update_post_meta($post_id, 'narnoo_product_video', json_encode($item->feature_video) );
					}else{
						delete_post_meta($post_id, 'narnoo_product_video');
					}
					/**
					*
					*	Import the brochure object
					*
					*/
					if(!empty($item->feature_print)){	

						update_post_meta($post_id, 'narnoo_product_print', json_encode($item->feature_print) );
					}else{

						delete_post_meta($post_id, 'narnoo_product_print');
					}

					
				}
			}
		} catch ( Exception $ex ) {
			self::ajax_fatal_error( __( 'Narnoo API error: ', NARNOO_OPERATOR_I18N_DOMAIN ) . $ex->getMessage() );
		}

		echo json_encode( $response );
		die();
	}
}
