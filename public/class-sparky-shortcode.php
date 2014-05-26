<?php

add_shortcode('sparky', 'sparky_shortcode' );

function sparky_shortcode( $atts ) {

	$atts = shortcode_atts(
		array(
			'id' 	=> false,
		),
		$atts
	);

	// no ID? fail
	if ( ! $atts['id'] ) return;

	// get post
	$post = get_post( $atts['id'] );
	
	// not a spark? fail
	if ( ! $post ) return;
	if ( $post->post_type != 'spark' ) return;
	
	// grab the post meta
	$core = get_post_meta( $post->ID, 'spark-core', true );
	$variable = get_post_meta( $post->ID, 'spark-variable', true );
	$cache = get_post_meta( $post->ID, 'spark-cache', true );
	
	// return the value
	$sa = new spark_api();
	$value = $sa->spark_variable( $core, $variable, $cache);

	if ( is_array($value) ) {
		$cachedvalue = $value['result'];
	} else {
		$cachedvalue = $variable . ' (' . $value .')';
	}

	return $cachedvalue;

}

add_shortcode('sparkystatus', 'sparky_status_shortcode' );

function sparky_status_shortcode( $atts ) {

	$atts = shortcode_atts(
		array(
			'id' 	=> false,
		),
		$atts
	);

	// no ID? fail
	if ( ! $atts['id'] ) return;

	// get post
	$post = get_post( $atts['id'] );
	
	// not a spark? fail
	if ( ! $post ) return;
	if ( $post->post_type != 'spark' ) return;
	
	// grab the post meta
	$core = get_post_meta( $post->ID, 'spark-core', true );
	$cache = get_post_meta( $post->ID, 'spark-cache', true );
	
	// return the value
	$sa = new spark_api();
	$status = $sa->spark_devices($cache);

	if ( is_array( $status ) ) { 
		foreach ($status as $stat) {
			if ( $stat['id'] == $core) {
				if ( $stat['connected'] ) {
					$online = true;
					$corestatus = __('Online', 'sparky');
				} else {
					$online = false;
					$corestatus = __('Offline', 'sparky');
				} 
			}
		}
	} else {
		$corestatus = $status;
	}

	return $corestatus;

}