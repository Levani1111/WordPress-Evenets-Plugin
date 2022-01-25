<?php
/**
 * Plugin Name: WP Events
 * Plugin URI:
 * Description: A plugin will add CPT for events.
 * Version: 1.0.0
 * Author: Levani Papashvili
 * 
 * */

//  Don't call the file directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/*-------------------------------------------*\
    Create Custom Post Type
\*-------------------------------------------*/
function lp_event_post_type() {
  register_post_type('event', array(
      'labels' => array(
          'name' => __('Events', 'vicodemedia'),
            'singular_name' => __('Event', 'vicodemedia'),
            'add_new' => __('Add New', 'vicodemedia'),
            'add_new_item' => __('Add New Event', 'vicodemedia'),
            'edit_item' => __('Edit Event', 'vicodemedia'),
            'new_item' => __('New Event', 'vicodemedia'),
            'view_item' => __('View Event', 'vicodemedia'),
            'view_items' => __('View Events', 'vicodemedia'),
            'search_items' => __('Search Events', 'vicodemedia'),
            'not_found' => __('No Events found', 'vicodemedia'),
            'not_found_in_trash' => __('No Events found in Trash', 'vicodemedia'),
            'all_items' => __('All Events', 'vicodemedia'),
            'archives' => __('Event Archives', 'vicodemedia'),
            'insert_into_item' => __('Insert into Event', 'vicodemedia'),
            'uploaded_to_this_item' => __('Uploaded to this Event', 'vicodemedia'),
            'filter_items_list' => __('Filter Events list', 'vicodemedia'),
            'items_list_navigation' => __('Events list navigation', 'vicodemedia'),
            'items_list' => __('Events list', 'vicodemedia'),
            'item_published' => __('Event published.', 'vicodemedia'),
            'item_published_privately' => __('Event published privately.', 'vicodemedia'),
            'item_reverted_to_draft' => __('Event reverted to draft.', 'vicodemedia'),
            'item_scheduled' => __('Event scheduled.', 'vicodemedia'),
            'item_updated' => __('Event updated.', 'vicodemedia'),
       ),
        'public' => true,
        'has_archive' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'show_in_nav_menus' => true,
        'show_in_admin_bar' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'comments', 'revisions', 'custom-fields', 'page-attributes', 'post-formats'),
        'can_export' => true,
    ));
}
add_action( 'init', 'lp_event_post_type' );

/*-------------------------------------------*\
    Add event date field to events post type
\*-------------------------------------------*/
function lp_add_post_meta_boxes() {
    add_meta_box(
       "post_metadata_events_post", // div id containig rendered fields
         "Event Date", // section heading displayed as text
         "post_meta_box_events_post", // callback function to render fields
            "event", // name of post type on wich to render the fields
            "side", // location of the screen
            "low" // placement priority 
    );
}
add_action( 'admin_init', 'lp_add_post_meta_boxes' );

/*-------------------------------------------*\
    Save field values
\*-------------------------------------------*/
function lp_save_post_meta_boxes(){
    global $post;
    if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    update_post_meta($post->ID, "_event_date", sanitize_text_field($_POST["_event_date"]));
}
add_action('save_post', 'lp_save_post_meta_boxes');

/*-------------------------------------------*\
    Callback function to render fields
\*-------------------------------------------*/
function post_meta_box_events_post(){
    global $post;
    $custom = get_post_custom($post->ID);
    $advertisingCategory = $custom[ "_event_date" ][0];
    echo "<input type=\"date\" name=\"_event_date\" value=\"".$advertisingCategory."\" placeholder=\"Event Date\">";
}

/*-------------------------------------------*\
    Generate shortcode
\*-------------------------------------------*/
function lp_events(){
    global $post;
    $args = array(
        'post_type'=>'event', 
        'post_status'=>'publish', 
        'posts_per_page'=>50, 
        'orderby'=>'meta_value',
        'meta_key' => '_event_date',
        'order'=>'ASC'
    );
    $query = new WP_Query($args);

    $content = '<ul>';
    if($query->have_posts()):
		while($query->have_posts()): $query->the_post();


            // trash event if old
            $exp_date = get_post_meta(get_the_ID(), '_event_date', true);
            // set the correct timezone
            date_default_timezone_set('America/New_York');
            $today = new DateTime();
            if($exp_date < $today->format('Y-m-d h:i:sa')){
                // Update post
                $current_post = get_post( get_the_ID(), 'ARRAY_A' );
                $current_post['post_status'] = 'trash';
                wp_update_post($current_post);
            }


            // display event
            $content .= '<li><a href="'.get_the_permalink().'">'. get_the_title() .'</a> - '.date_format(date_create(get_post_meta($post->ID, '_event_date', true)), 'jS F').'</li>'; 
        endwhile;
    else: 
        _e('Sorry, nothing to display.', 'vicodemedia');
    endif;
    $content .= '</ul>';

    return $content;
}
add_shortcode('events-list', 'lp_events');

/*-------------------------------------------*\
    Assing custom template to event post type
\*-------------------------------------------*/
function load_event_template( $template ) {
    global $post;
    if ( 'event' === $post->post_type && locate_template( array( 'single-event.php' ) ) !== $template ) {
        return plugin_dir_path( __FILE__ ) . 'single-event.php';
    }

    return $template;
}

add_filter( 'single_template', 'load_event_template' );

