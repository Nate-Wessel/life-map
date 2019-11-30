<?php
//enable [sitemap] shortcode
// this prints some nested lists with a tree structure
// strata are unordered, but posts/events themselves are ordered

function tab($num=0){ return str_repeat("\t",$num); }

function print_strata_recursive($parentID,$level=0){
	$baseIndent = 3*$level; 
	$strata = get_categories(array('taxonomy'=>'strata','parent'=>$parentID));
	if(sizeof($strata)==0){ return; } // if nothing to print, print nothing
	$val = tab($baseIndent+1)."<ul class='strata'>\n"; 
	foreach($strata as $stratum){
		$displayValue = get_term_meta($stratum->term_id,'display',true);
		$displayValue = $displayValue == 'true' ? 'true' : 'false';
		$val .= tab($baseIndent+2)."<li class='stratum' data-stratum='$stratum->slug' ";
		$val .= "data-display='$displayValue' data-level='$level'>\n";
		$val .= tab($baseIndent+3)."<span class='stratum-name'>$stratum->name</span>\n";
		// print direct child events if any
		$val .= print_child_posts_list($stratum->slug,$baseIndent+3);
		// get child categories
		$val .= print_strata_recursive($stratum->term_id,$level+1);
		$val .= tab($baseIndent+2)."</li><!--end $stratum->slug-->\n";
	}
	$val .= tab($baseIndent+1)."</ul>\n";
	return $val;
}

function print_child_posts_list($stratumSlug,$indentLevel=0){
	# find posts or pages (events) in the specified filum
	$wpq = new WP_Query(array(
		'post_type'=>array('post','page','cv_event'),
		'tax_query'=>array(array(
			'taxonomy'=>'strata', 'field'=>'slug', 'include_children'=>false,
			'terms'=>$stratumSlug
		))
	));
	if(sizeof($wpq->posts)==0){ return ''; }
	$val = tab($indentLevel)."<ol>\n";
	foreach($wpq->posts as $i=>$post){
		$val .= tab($indentLevel+1)."<li class='eventus' data-node-id='$post->ID' ";
		$val .= "data-date='$post->post_date'>\n";
		$val .= tab($indentLevel+2)."'". substr($post->post_date,2,2);
		$val .= " <a href='".get_permalink($post->ID)."'>$post->post_title</a>\n";
		$val .= tab($indentLevel+1)."</li>\n"; // eventus
	}
	$val .= tab($indentLevel)."</ol>\n";
	return $val;
}

function cv_get_event_data_JSON(){
	# get all cv_events and their properties
	$posts = get_posts(array( 'post_type'=>'cv_event', 'numberposts'=>-1 ));
	$data = array( 'events'=>[], 'links'=>[] );
	foreach( $posts as $post){
		# set ID and title for every event
		$data['events'][$post->ID] = [
			'id'=> $post->ID,
			'title'=>$post->post_title,
			'href'=>get_permalink($post_id),
		];
		# set dates if they exist
		if(($start = get_post_meta($post->ID, "start", true)) != '' ){
			$data['events'][$post->ID]['start'] = $start; 
		}
		if(($end = get_post_meta($post->ID, "end", true)) != '' ){
			$data['events'][$post->ID]['end'] = $end; 
		}
		# set strata if any
		$strata = wp_get_post_terms($post->ID,'strata');
		foreach( $strata as $stratum){	
			$data['events'][$post->ID]['strata'][] = $stratum->slug;
		}
		# add a link for a parent relationship if any
		if( $post->post_parent != 0 ){
			$data['links'][] = [
				'from'=>$post->ID, 'to'=>$post->post_parent,
				'type'=>'constitutive'
			];
		}
	}
	return json_encode($data,JSON_PRETTY_PRINT);
}

function sitemap_shortcode_handler( $atts ){
	$val = "<div id='chartaData'>\n";
	$val .= print_strata_recursive(0); // top level parent is 0
	$val .= "</div>\n";
	$val .= "\n<script>\nvar cv_data =".cv_get_event_data_JSON()."\n</script>";
	return $val;
}
add_shortcode( 'sitemap', 'sitemap_shortcode_handler' );

# conditionally add javascript to header when shortcode found on page
# thanks to: http://beerpla.net/2010/01/13/wordpress-plugin-development-how-to-include-css-and-javascript-conditionally-and-only-when-needed-by-the-posts/
add_filter('the_posts', 'conditionally_add_scripts_and_styles'); // the_posts gets triggered before wp_head
function conditionally_add_scripts_and_styles($posts){
	if (empty($posts)) return $posts;
	$shortcode_found = false; // use this flag to see if styles and scripts need to be enqueued
	foreach ($posts as $post) {
		if (stripos($post->post_content, '[sitemap]') !== false) {
			$shortcode_found = true; // bingo!
			break;
		}
	} 
	if ($shortcode_found) {
		// enqueue here
		wp_enqueue_script('d3v4','/wp-content/plugins/charta-vitae/d3/d3.v4.js');
		wp_enqueue_script('charta-vitae','/wp-content/plugins/charta-vitae/charta-vitae.js',array('d3v4'));
		wp_enqueue_style('charta-vitae-svg','/wp-content/plugins/charta-vitae/charta.css');
	}
	return $posts;
}
?>
