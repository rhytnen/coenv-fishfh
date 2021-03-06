<?php
// Various clean up functions
//require_once('library/cleanup.php');

// Required for Foundation to work properly
require_once('library/breadcrumbs.php');

// Required for Foundation to work properly
require_once('library/foundation.php');

// Add menu walker
require_once('library/menu-walker.php');
require_once('library/walker-top-menu.php');

// Base navigation
require_once('library/navigation.php');

// Widgets
require_once('library/widgets.php');

// Return entry meta information for posts
require_once('library/entry-meta.php');

// Enqueue scripts
require_once('library/enqueue-scripts.php');

// Add theme support
require_once('library/theme-support.php');

// Photo functions
require_once('library/photos.php');

// Setting fields for address, phone, social media
require_once('library/admin-setting-fields.php');

// Need to be sorted into includes




/**
 * Image sizes
 */

add_image_size( 'med_sq', '240', '240', true );
add_image_size( 'sm_sq', '120', '120', true );
add_image_size( 'faculty_sm', '120', '120', true );

add_image_size( 'news_small', '250', '188', true );
add_image_size( 'news_medium', '370', '276', true );
add_image_size( 'news_large', '425', '317', true );




/**
 * Gets the top-level ancestor for pages, posts and custom post types
 * Credit: https://github.com/elcontraption/wp-tools 
 * @param
 * - string
 * @return 
 * - array
 */
function coenv_base_get_ancestor($attr = 'ID') {
	
	$post = get_queried_object();

	// test for search
	if ( is_search() ) {
		return false;
	}

	if ( ($post->post_type == 'post' || is_archive() || is_search())) {

		$page_for_posts = get_option( 'page_for_posts' );

		if ( $page_for_posts == 0 ) {
			return false;
		}

		$ancestor = get_post( $page_for_posts );
		return $ancestor->$attr;
	}

	// test for pages
	if ( $post->post_type == 'page' ) {

		// test for top-level pages
		if ( $post->post_parent == 0 ) {
			return $post->$attr;
		}

		// must be a child page
		$ancestors = get_post_ancestors( $post->ID );
		$ancestor = get_post( array_pop( $ancestors ) );
		return $ancestor->$attr;
	}

	// test for custom post types
	$custom_post_types = get_post_types( array( '_builtin' => false ), 'object' );
	if ( !empty( $custom_post_types ) && array_key_exists( $post->post_type, $custom_post_types ) ) {

		// is parent_page slug defined?
		if ( isset( $custom_post_types[ $post->post_type ]->parent_page ) ) {

			// parent_page slug is defined.
			$parent = get_page_by_path( $custom_post_types[ $post->post_type ]->parent_page );

		} else {

			// parent_page slug is not defined
			// find custom slug
			$slug = $custom_post_types[ $post->post_type ]->rewrite[ 'slug' ];

			// if a page exists with the same slug, assume that's the parent page
			$parent = get_page_by_path( $slug );
		}

		// get ancestors of $parent
		$ancestors = get_post_ancestors( $parent->ID );

		// if ancestors is empty, just return $parent;
		if ( empty( $ancestors ) ) {
			return $parent->$attr;
		}

		$ancestor = get_post( array_pop( $ancestors ) );
		return $ancestor->$attr;
	}
}

// page/post ids to exclude from the main menu
function coenv_base_menu_exclude() {
// args
$args = array(
	'numberposts' => -1,
	'posts_per_page'=> -1,
	'post_type' => 'page',
	'meta_key'=>'menu_visibility',
    'meta_value'=> 'not-visible',
    'meta_compare'=>'='
);

// get results
$nav_exclude = array();
$nav_query = new WP_Query( $args );


if( $nav_query->have_posts() ):
	while ( $nav_query->have_posts() ) : $nav_query->the_post();
		$nav_exclude[] = get_the_ID();
	endwhile;
endif;

wp_reset_query();

return $nav_exclude;
wp_reset_query();
}


/* 
 * Remove underline from both Full and Basic TinyMCE toolbars in ACF
 */
add_filter( 'acf/fields/wysiwyg/toolbars' , 'coenv_base_acf_toolbar'  );
function coenv_base_acf_toolbar( $toolbars ) {

	if( ($key = array_search('underline' , $toolbars['Basic' ][1])) !== false ) {
	    unset( $toolbars['Basic' ][1][$key] );
	}
	if( ($key = array_search('underline' , $toolbars['Full' ][2])) !== false ) {
	    unset( $toolbars['Full' ][2][$key] );
	}

	// return $toolbars - IMPORTANT!
	return $toolbars;
}

/* 
 * Return blog taxonomy terms.
 */
function coenv_base_blog_terms($id) {
	$blog_terms = wp_get_post_terms( $id, 'student_blog' );
	if ($blog_terms) {
		echo '<ul class="blog-terms inline-list">';
		foreach ($blog_terms as $term) {
			echo '<li><a class="button" href="/students/student-blog/?blog-cat=' . $term->slug . '">' . $term->name . '</a></li>';
		}
		echo '</ul>';
	}
}
add_filter('shortcode_atts_gallery','overwrite_gallery_atts_wpse_95965',10,3);
function overwrite_gallery_atts_wpse_95965($out, $pairs, $atts){
    // force the link='file' gallery shortcode attribute:
    $out['link']='file'; 
    return $out;
}

function foo_register_alt_version_features($features) {
    $features['feature_name'] = array(
        '_foo_meta_key1',
        '_foo_meta_key2'
    );
    return $features;
}

add_filter('bu_alt_versions_feature_support', 'foo_register_alt_version_features');

/* 
 * Category filters for WPQuery templates (blog, publications, faculty, etc.)
 */
function coenv_base_cat_filter($tax,$tax_value) {

$tax_obj = get_taxonomy($tax);
$tax_str = $tax_obj->labels->name;

$cats_args  = array(
	'exclude' => '1',
	'orderby' => 'name',
	'order' => 'ASC',
	'taxonomy' => $tax
);
$cats = get_categories($cats_args);
	if ($cats) {
		echo '<select name="select-category" class="select-category">';
		echo '<option class="level-0" value="' . strtok($_SERVER['REQUEST_URI'],'?') . '">Chooose a category</option>';
		echo '<ul class="select-category small-block-grid-3">';
 		foreach($cats as $cat) { 
			$selected = $cat->slug == $tax_value ? ' selected="selected"' : '';
			echo '<option value="?tax=' . $tax . '&term=' . $cat->slug . '"' . $selected . '>' . $cat->name . '</option>';
			echo '<li><a href="?tax=' . $tax . '&term=' . $cat->slug . '#filter' . '">' . $cat->name . '</a></li>';
 		}
		echo '</select>';
		echo '</ul>';
 	}
}

/* 
 * Date filters for WPQuery templates (blog, publications, faculty, etc.)
 */
function coenv_base_date_filter($post_type,$coenv_month,$coenv_year) {
	$counter = 0;
	$ref_month = '';
	$monthly = new WP_Query(array('posts_per_page' => -1, 'post_type'	=> $post_type));
	echo '<select name="select-category" class="select-category">';
	echo '<option value="' . strtok($_SERVER['REQUEST_URI'],'?') . '">Choose a Date</option>';
	if( $monthly->have_posts() ) :
		while( $monthly->have_posts() ) : $monthly->the_post();
		    if( get_the_date('mY') != $ref_month ) {
		    	$month_num = get_the_date('m');
		    	$month_str = get_the_date('F');
		    	$year_num = get_the_date('Y');
		    	if ($year_num == $coenv_year && $month_num == $coenv_month) {
		    	 $selected = ' selected="selected"';
		    	} else {
		    		$selected = '';
		    	}
		    	echo '<option value="page/1/?coenv-year=' . $year_num . '&coenv-month=' . $month_num  . '"' . $selected . '>' . $month_str . ' ' . $year_num . '</option>';
		       // echo "\n".get_the_date('F Y');
		        $ref_month = get_the_date('mY');
		        $counter = 0;
		    }
		endwhile; 
	endif;
	echo '</select>';
	wp_reset_postdata();
	wp_reset_query();
}
/* 
 * Serve images over SSL, if enabled
 */
function coenv_url_ssl($url)
{
  if( function_exists('is_ssl') && is_ssl() )
  {
    return str_replace('http://', 'https://', $url);
  }
  return $url;
}
add_filter('wp_get_attachment_url', 'coenv_url_ssl');

/* 
 * Redirect to homepage after password reset
 */
add_action( "password_reset", "rngs_password_reset", 10, 2 );

/**
 * Implement "password_reset" for RNGS
 *
 * After a password reset has been performed we want the Log in link to redirect the user to the home url.
 * When we see this action being run we know that we should be filtering "login_url" to add the redirect the home page.
 * We don't filter "login_url" any other time. 
 *
 * @param WP_User $user - the user object
 * @param string $new_pass - the new password
 *  
 */
function rngs_password_reset( $user, $new_pass ) {
  add_filter( "login_url", "rngs_login_url", 10, 2 );
}

/**
 *  Implement "login_url" filter for RNGS
 *
 * Redirect the user to the home page after logging in
 *
 * @TODO - make this an option field that controls where the logged in user goes
 * @TODO - dependent upon role?
 * 
 * @param string $login_url - the original login_url which is not expected to include "redirect_to" or "reauth"
 * @param string $redirect - expected to be null/blank
 */
function rngs_login_url( $login_url, $redirect ) {
  $home_redirect = home_url();
  $login_url = add_query_arg('redirect_to', urlencode( $home_redirect ), $login_url);
  return( $login_url );
} 
