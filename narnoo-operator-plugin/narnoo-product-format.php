<?php


/**
*
* @date_created: 18.10.2017
* @description: Displays any featured products
* @param: Number to display
* @param: Markup ( html with {{handlebar}} markup )
* @return: (string) HTML
*
*/
function get_narnoo_featured_products( $number=10,  $markup = NULL, $post_id = NULL ){

	// The Query
	$args = array(
		'post_type' 		=> 'narnoo_product',
		'posts_per_page' 	=> $number,
	    'meta_key' 		    => 'narnoo_featured_product'
	);

	if( !empty($post_id) ){
		$args['post__not_in'] = array( $post_id ); 
	}

	$the_query = new WP_Query( $args );

	// The Loop
	if ( $the_query->have_posts() ) {
		
		$render = "";

		if( !empty($markup) ){

			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				//find and replace the title.
				$_html = str_replace('{{title}}', 	  	 get_the_title($the_query->post->ID),     $markup);
				//find and replace the link.
				$_html = str_replace('{{permaLink}}', 	 get_the_permalink($the_query->post->ID), $_html);
				//find and replace the featureImage.
				$_html = str_replace('{{featureImage}}', get_the_post_thumbnail_url($the_query->post->ID), $_html);

				/**
				*
				*	meta post options
				*
				*/
				$minPrice  = get_post_meta($the_query->post->ID, 'product_min_price', true); //done
				if(!empty($minPrice)){
					$_html = str_replace('{{price}}', $minPrice, $_html);
				}



				$render .= $_html;

			}

		}else{
			$render .= '<ul>';
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$render .= '<li><a href="'.get_the_permalink().'">' . get_the_title() . '</a></li>';
			}
			$render .= '</ul>';
		}


		/* Restore original Post Data */
		wp_reset_postdata();

		return $render;

	} else {
		return NULL;
	}

}

/**
*
* @date_created: 12.10.2017
* @description: Displays any related products
* @param: Number to display
* @param: Markup ( html with {{handlebar}} markup )
* @return: (string) HTML
*
*/
function get_narnoo_related_products( $number=10, $markup = NULL, $post_id = NULL ){

	// The Query
	$args = array(
	'post_type' 		=> 'narnoo_product',
	'posts_per_page' 	=> $number
	);

	if( !empty($post_id) ){
		$args['post__not_in'] = array( $post_id ); 
	}
	$the_query = new WP_Query( $args );

	// The Loop
	if ( $the_query->have_posts() ) {
		
		$render = "";

		if( !empty($markup) ){

			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				//find and replace the title.
				$_html = str_replace('{{title}}', 	  	 get_the_title($the_query->post->ID),     $markup);
				//find and replace the link.
				$_html = str_replace('{{permaLink}}', 	 get_the_permalink($the_query->post->ID), $_html);
				//find and replace the featureImage.
				$_html = str_replace('{{featureImage}}', get_the_post_thumbnail_url($the_query->post->ID), $_html);

				/**
				*
				*	meta post options
				*
				*/

				$minPrice  = get_post_meta($the_query->post->ID, 'product_min_price', true); //done
				if(!empty($minPrice)){
					$_html = str_replace('{{price}}', $minPrice, $_html);
				}


				$render .= $_html;

			}

		}else{
			$render .= '<ul>';
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$render .= '<li><a href="'.get_the_permalink().'">' . get_the_title() . '</a></li>';
			}
			$render .= '</ul>';
		}


		/* Restore original Post Data */
		wp_reset_postdata();

		return $render;

	} else {
		return NULL;
	}

}





/**
*
* @date_created: 28.09.2017
* @description: Booking button text
* @param: Booking information stored in for the post
*
*/
function narnoo_booking_button($data, $markup=NULL){

	if( !empty($data) ){

		if( empty($markup) ){
			return '<a class="btn btn-primary" href="'.$data.'" target="_blank">Book Now!</a>';
		}else{
			

			/**
			*
			*	options.
			*		type: class - target - label
			*
			*/
			if(is_array($markup)){
				
				$html = '<a ';

				if( !empty( $markup['class'] ) ){
					$html .= 'style="'.$markup['class'].'" ';
				}
				if( !empty( $markup['target'] ) ){
					$html .= 'target="'.$markup['target'].'" ';
				}

				$html .= 'href="'.$data.'" >';
				
				if( !empty( $markup['label'] ) ){
					$html .= $markup['label'];
				}else{
					$html .= 'Book Now';
				}
				$html .= '</a>';

				return $html;



			}else{
				return "Markup must be an array!";
			}

		}
	

		
	}else{
		return NULL;
	}

}
/**
*
* @date_created: 28.09.2017
* @description: Booking button text
* @param: Booking information stored in for the post
*
*/
function narnoo_price_format( $data,$currency='AUD',$markup=NULL ){

	if( !empty($data) ){

		if( !empty($markup) ){
			return str_replace('{{content}}', $data, $markup);
		}else{
			return '$'.$data;
		}

	}else{
		return NULL;
	}

}
/**
*
* @date_created: 28.09.2017
* @description: Booking button text
* @param: Booking information stored in for the post
*
*/
function narnoo_duration($data,$markup=NULL){

	if( !empty($data) ){

		if($data > 24){
			$duration = $data/24;
			$_period  = 'DAYS';
			$_text 	  = $duration.' '.$_period;
		}else{
			$_text 	  = $data.' HRS';
		}

		if( !empty($markup) ){
			return str_replace('{{content}}', $_text, $markup);
		}else{
			return $_text;
		}


	}else{
		return NULL;
	}

}
/**
*
* @date_created: 28.09.2017
* @description: Booking button text
* @param: Booking information stored in for the post
*
*/
function narnoo_text_format($data,$markup=NULL){

	if( !empty($data) ){

		if( !empty($markup) ){
			return str_replace('{{content}}', $data, $markup);
		}else{
			return $data;
		}

	}else{
		return NULL;
	}

}
/**
*
* @date_created: 28.09.2017
* @description: Returns the video ID so we can pass to shortcodes
* @param: Video data array
* @return: id int
*
*/
function narnoo_get_product_video($video, $markup=NULL){

	if( !empty($video) ){

		$_video = json_decode($video);

		if(empty($markup)){
			return '<iframe width="560" height="315" src="' . $_video->video_embed_link . '" frameborder="0" allowfullscreen></iframe>';
		}else{

			/**
			*
			*	options.
			*		type: Embed - Id - embed_source
			*
			*/
			if(is_array($markup)){

				if(!empty($markup['type'])){
					$type = lcfirst( $markup['type'] );
					switch ($type) {
						case 'embed':
							$type = 'embed';
							break;
						case 'id':
							$type = 'id';
							break;
						case 'link':
							$type = 'link';
							break;
						default:
							$type = 'embed';
							break;
					}
				}else{
					$type = 'embed';
				}
				if( !empty( $markup['width'] ) ){
					$width = $markup['width'];
				}else{
					$width = '560';
				}
				if( !empty( $markup['height'] ) ){
					$height = $markup['height'];
				}else{
					$height = '315';
				}

				if( !empty( $markup['container_class'] ) ){
					$class = $markup['container_class'];
				}

				$dia = TRUE;
				if( !empty( $markup['remove_sizing'] ) ){
					$dia = FALSE;
				}

				if($type == 'embed'){
					
					$embedHtml = '<iframe';
					if(!empty($class)){
						$embedHtml .= ' style="' . $class . '" ';
					} 
					if(!empty($dia)){
						$embedHtml .= ' width="'.$width.'" height="'.$height.'"  ';
					} 
					$embedHtml .= 'src="' . $_video->video_embed_link . '" frameborder="0" allowfullscreen></iframe>';
				
					return $embedHtml;


				}elseif($type == 'id'){
					return $_video->video_id;
				}else{
					return $_video->video_embed_link;
				}

			}else{
				return 'Video markup needs to be an array';
			}


		}  

	}else{
		return NULL;
	}
}
/**
*
* @date_created: 28.09.2017
* @description: Returns the print details so we can pass to shortcodes
* @param: print data array
* @return: id int
*
*
*
*	type: Link || Thumbnail Button || ID
*	size: 
*
*
*
*
*/
function narnoo_get_product_print($print, $markup = NULL){

	if(!empty($print)){

		$_print = json_decode($print);

		if(!empty($markup['type'])){			
			$type = lcfirst( $markup['type'] );
			switch ($type) {
				case 'id':
					$type = 'id';
					break;
				case 'link':
					$type = 'link';
					break;
				default:
					$type = 'link';
					break;
			}
		}else{
			$type = 'link';
		}

		
		if($type == 'id'){
			$html = $_print->print_id;
		}elseif ($type == 'link') {
			

			if(empty($markup['thumbnail'])){
				$html = $_print->preview_image_path;
			}else{


				$_size = lcfirst($markup['thumbnail']);
				if($_size == 'crop'){
					$img = $_print->crop_image_path;
				}elseif($_size == 'xcrop'){
					$img = $_print->xcrop_image_path;
				}elseif($_size == '400'){
					$img = $_print->image_400_path;
				}elseif($_size == '800'){
					$img = $_print->image_800_path;
				}elseif($_size == 'preview'){
					$img = $_print->preview_image_path;
				}else{
					$img = $_print->thumb_image_path;
				}


				$html = '<a href="'.$_print->file_path.'" target="_blank">';
				$html .= '<img';
					if( !empty($markup['class']) ){
					$html .= ' class="'.$markup['class'].'"';
					}
					if( !empty($markup['height']) ){
					$html .= ' height="'.$markup['height'].'"';
					}
					if( !empty($markup['width']) ){
					$html .= ' width="'.$markup['width'].'"';
					}
				$html .= ' src="'.$img.'">';
				$html .= '</a>';

			}



		}


		return $html;

	}else{
		return NULL;
	}
}

/**
*
* @date_created: 28.09.2017
* @description: Creates markup for the gallery and outputs this to the screen
* @param: Gallery Array
* @param: Markup Array
*
*/
function narnoo_get_product_gallery($gallery,$markup=NULL,$raw=NULL){

	
	if( !empty($gallery) ){

		if(!empty($raw)){

			return $gallery->image;

		}else{
            

		if( !empty($markup) ){

			if(!empty($markup['size'])){
				$size 		= $markup['size'];
			}else{
				$size 		= 'large';
			}
			if(!empty($markup['container'])){
				$container 	= $markup['container'];
			}else{
				$container 		 = '<li>';
				$containerClose  = '</li>';
			}

			if(!empty($markup['container_class'])){

				$_containerStyle = ' style="'.$markup['container_class'].'"';
				$containerClose  = substr_replace($container, '/', 1, 0);
				$container 		 = substr_replace($container, $_containerStyle, -1, 0);
			}

			if( !empty($markup['image_class']) ){

				$_imageStyle = 'style="'.$markup['image_class'].'" ';
			}

			
		}else{
			$size 			 = 'large';
			$container  	 = '<li>';
			$containerClose  = '</li>';
		}


        foreach ($gallery->image as $img) {
        
        $html .=	$container;

         if($size == 'xxlarge'){

         	$html .= '<img ';
         	if( !empty($_imageStyle) ){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->xxlarge_image_path.'" />';

         }elseif ($size == 'xlarge') {

         	$html .= '<img ';
         	if(!empty($_imageStyle)){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->xlarge_image_path.'" />';

         }elseif ($size == '800') {

         	$html .= '<img ';
         	if(!empty($_imageStyle)){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->image_800_path.'" />';

         }elseif ($size == '400') {

         	$html .= '<img ';
         	if(!empty($_imageStyle)){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->image_400_path.'" />';

         }elseif ($size == '200') {

         	$html .= '<img ';
         	if(!empty($_imageStyle)){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->image_200_path.'" />';

         }elseif ($size == 'crop') {

         	$html .= '<img ';
         	if(!empty($_imageStyle)){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->crop_image_path.'" />';

         }elseif ($size == 'preview') {

         	$html .= '<img ';
         	if(!empty($_imageStyle)){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->preview_image_path.'" />';

         }elseif ($size == 'xcrop') {

         	$html .= '<img ';
         	if(!empty($_imageStyle)){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->xcrop_image_path.'" />';

         }elseif ($size == 'thumb') {

         	$html .= '<img ';
         	if(!empty($_imageStyle)){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->thumb_image_path.'" />';

         }else {

         	$html .= '<img ';
         	if(!empty($_imageStyle)){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->large_image_path.'" />';

         }
         
         $html .=    $containerClose; 

         }
                      
         return $html;

     }

    }else{
		return NULL;
	}

}

/**
*
* @date_created: 28.09.2017
* @description: Creates markup for the gallery and outputs this to the screen
* @param: Gallery Array
* @param: Markup Array
*
*/
function narnoo_get_product_gallery_display($gallery,$markup=NULL,$raw=NULL){

	
	if( !empty($gallery) ){

		if(!empty($raw)){

			return $gallery->image;

		}else{
            

		if( !empty($markup) ){

			if(!empty($markup['size'])){
				$size 		= $markup['size'];
			}else{
				$size 		= 'large';
			}
			if(!empty($markup['container'])){
				$container 	= $markup['container'];
			}else{
				$container 		 = '<li>';
				$containerClose  = '</li>';
			}

			if(!empty($markup['container_class'])){

				$_containerStyle = ' style="'.$markup['container_class'].'"';
				$containerClose  = substr_replace($container, '/', 1, 0);
				$container 		 = substr_replace($container, $_containerStyle, -1, 0);
			}

			if( !empty($markup['image_class']) ){

				$_imageStyle = 'style="'.$markup['image_class'].'" ';
			}

			
		}else{
			$size 			 = 'large';
			$container  	 = '<li>';
			$containerClose  = '</li>';
		}


        foreach ($gallery->image as $img) {
        
        $html .=	$container;

         if($size == 'xxlarge'){

         	$html .= '<img ';
         	if( !empty($_imageStyle) ){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->xxlarge_image_path.'" />';

         }elseif ($size == 'xlarge') {

         	$html .= '<img ';
         	if(!empty($_imageStyle)){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->xlarge_image_path.'" />';

         }elseif ($size == '800') {

         	$html .= '<img ';
         	if(!empty($_imageStyle)){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->image_800_path.'" />';

         }elseif ($size == '400') {

         	$html .= '<img ';
         	if(!empty($_imageStyle)){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->image_400_path.'" />';

         }elseif ($size == '200') {

         	$html .= '<img ';
         	if(!empty($_imageStyle)){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->image_200_path.'" />';

         }elseif ($size == 'crop') {

         	$html .= '<img ';
         	if(!empty($_imageStyle)){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->crop_image_path.'" />';

         }elseif ($size == 'preview') {

         	$html .= '<img ';
         	if(!empty($_imageStyle)){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->preview_image_path.'" />';

         }elseif ($size == 'xcrop') {

         	$html .= '<img ';
         	if(!empty($_imageStyle)){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->xcrop_image_path.'" />';

         }elseif ($size == 'thumb') {

         	$html .= '<img ';
         	if(!empty($_imageStyle)){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->thumb_image_path.'" />';

         }else {

         	$html .= '<img ';
         	if(!empty($_imageStyle)){
         		$html .= $_imageStyle;
         	}
         	$html .= 'src="'.$img->large_image_path.'" />';

         }
         
         $html .=    $containerClose; 

         }
                      
         return $html;

     }

    }else{
		return NULL;
	}

}



/****************************************************************************************
*							
*					WORDPRESS POST META DATA STORED FOR POST
*
****************************************************************************************/



$productId          = get_post_meta(get_the_ID(), 'narnoo_product_id', 		 true); //done
$narnooId 			= get_post_meta(get_the_ID(), 'narnoo_product_id',       true); //done
$minPrice 			= get_post_meta(get_the_ID(), 'product_min_price',       true); //done
$avgPrice 			= get_post_meta(get_the_ID(), 'product_avg_price',       true); //done
$maxPrice 			= get_post_meta(get_the_ID(), 'product_max_price',       true); //done
$bookingLink   		= get_post_meta(get_the_ID(), 'product_booking_link',    true); //done
$gallery  			= json_decode( get_post_meta(get_the_ID(), 'narnoo_product_gallery',  true) );

$video     = get_post_meta(get_the_ID(), 'narnoo_product_video',    true);
$print     = get_post_meta(get_the_ID(), 'narnoo_product_print',    true);

$isAttraction = get_option('narnoo_operator_category');
if(!empty($isAttraction) && $isAttraction == 'attraction'){

    $duration 	= get_post_meta(get_the_ID(), 'narnoo_product_duration',    true); //done
    $startTime  = get_post_meta(get_the_ID(), 'narnoo_product_start_time',  true);
    $endTime    = get_post_meta(get_the_ID(), 'narnoo_product_end_time',    true);
    $transport  = get_post_meta(get_the_ID(), 'narnoo_product_transport',   true); //done
    $purchase 	= get_post_meta(get_the_ID(), 'narnoo_product_purchase',    true); //done
    $health   	= get_post_meta(get_the_ID(), 'narnoo_product_health',      true); //done
    $packing  	= get_post_meta(get_the_ID(), 'narnoo_product_packing',     true); //done
    $children 	= get_post_meta(get_the_ID(), 'narnoo_product_children',    true); //done
    $addition 	= get_post_meta(get_the_ID(), 'narnoo_product_additional',  true); //done
    $itinerary 	= get_post_meta(get_the_ID(), 'product_itinerary',  		true); //done
    
}



?>