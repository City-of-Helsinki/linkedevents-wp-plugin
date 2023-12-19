<?php

namespace Evermade\LinkedEvents\Rewrites;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Content for single store pages are loaded from the LinkedEvents API. We need to create "virtual" pages
 * for that.
 *
 * We will create an url rewrite:
 * /liike/123/liikkeennimi
 *
 * And we will rewrite this to the page called Single store. That page then loads the content from the API.
 */

function get_linked_events_page_id( string $name ): int {
	$posts = get_posts(
	    array(
	        'post_type'              => 'page',
	        'title'                  => $name,
	        'post_status'            => 'all',
	        'numberposts'            => 1,
	        'update_post_term_cache' => false,
	        'update_post_meta_cache' => false,
	        'orderby'                => 'post_date ID',
	        'order'                  => 'ASC',
			'fields'				 => 'ids',
			'no_found_rows'			 => true,
	    )
	);

	return $posts ? $posts[0] : 0;
}


/**
 * Init rewrites.
 */
function init() {

    global $wp_rewrite, $wp;

    // Rewrite stores.
    $page_id = get_linked_events_page_id('LinkedEvents');
    if ( $page_id ) {
        add_rewrite_rule('^event/([a-zA-Z0-9:]+)/.*', 'index.php?page_id=' . $page_id . '&store_id=$matches[1]', 'top');
    }

    // // Rewrite offers.
    // $page_id = get_linked_events_page_id('Single offer');
    // if ( $page_id ) {
    //     add_rewrite_rule('^tarjous/([0-9]+)/.*', 'index.php?page_id=' . $page_id . '&offer_id=$matches[1]', 'top');
    // }

    // // Rewrite news items.
    // $page_id = get_linked_events_page_id('Single news item');
    // if ( $page_id ) {
    //     add_rewrite_rule('^uutinen/([0-9]+)/.*', 'index.php?page_id=' . $page_id . '&news_item_id=$matches[1]', 'top');
    // }

    // Flush rules (heavy operation).
    $wp_rewrite->flush_rules(false);

}
add_action('init', __NAMESPACE__ . '\\init');



/**
* Init additional query var.
*/
function init_query_vars() {
    global $wp;
    $wp->add_query_var('store_id');
    $wp->add_query_var('offer_id');
    $wp->add_query_var('news_item_id');
}
add_action('init', __NAMESPACE__ . '\\init_query_vars');



/**
* Alter title if we are on API page.
*
* @param       string    $title    Default title text for current view.
* @param       string    $sep      Optional separator.
* @return      string              The filtered title.
*/
function event_title_in_wp_title( $title ) {
	return should_rewrite_wp_title() && api_item_title()
		? api_item_title() . ' | ' . get_bloginfo('title')
		: $title;
}
add_filter( 'wp_title', __NAMESPACE__ . '\\event_title_in_wp_title', 100 );
add_filter( 'document_title', __NAMESPACE__ . '\\event_title_in_wp_title', 100 );

function api_item_title(): string {
	return $GLOBALS['api_item_title'] ?? '';
}

function should_rewrite_wp_title(): bool {
	return apply_filters( 'linked_events_is_wp_title_rewrite_enabled', false );
}
