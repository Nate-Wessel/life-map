<?php get_header(); ?>

<h1><?php echo get_the_title(); ?></h1>
<!--TODO add proper dynamic link-->
<p><a href="/portfolio/data-viz/charta-vitae/">Back to Map</a></p>
<?php if(have_posts()){ the_post();
	$start = get_post_meta($post->ID,'start',true);
	$end = get_post_meta($post->ID,'end',true);
	echo "<p>Starts: $start, Ends: $end</p>";
	the_content(); 
} // end the loop 
get_footer();
?>
