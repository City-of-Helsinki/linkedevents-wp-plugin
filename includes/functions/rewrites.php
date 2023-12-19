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



/**
 * Init rewrites.
 */
function init() {

    global $wp_rewrite, $wp;

    // Rewrite stores.
    $page = get_page_by_title('LinkedEvents');
    if ($page) {
        $id = $page->ID;
        add_rewrite_rule('^event/([a-zA-Z0-9:]+)/.*', 'index.php?page_id=' . $id . '&store_id=$matches[1]', 'top');
    }

    // // Rewrite offers.
    // $page = get_page_by_title('Single offer');
    // if ($page) {
    //     $id = $page->ID;
    //     add_rewrite_rule('^tarjous/([0-9]+)/.*', 'index.php?page_id=' . $id . '&offer_id=$matches[1]', 'top');
    // }

    // // Rewrite news items.
    // $page = get_page_by_title('Single news item');
    // if ($page) {
    //     $id = $page->ID;
    //     add_rewrite_rule('^uutinen/([0-9]+)/.*', 'index.php?page_id=' . $id . '&news_item_id=$matches[1]', 'top');
    // }

    // Flush rules (heavy operation).
    $wp_rewrite->flush_rules(false);

}
add_action('init', '\Evermade\LinkedEvents\Rewrites\init');



/**
* Init additional query var.
*/
function init_query_vars() {
    global $wp;
    $wp->add_query_var('store_id');
    $wp->add_query_var('offer_id');
    $wp->add_query_var('news_item_id');
}
add_action('init', '\Evermade\LinkedEvents\Rewrites\init_query_vars');



/**
* Alter title if we are on API page.
*
* @param       string    $title    Default title text for current view.
* @param       string    $sep      Optional separator.
* @return      string              The filtered title.
*/
function fairapp_wp_title( $title, $sep ) {
	return should_rewrite_wp_title() && api_title_title()
		? api_title_title() . ' | ' . get_bloginfo('title')
		: $title;
}
add_filter( 'wp_title', 'fairapp_wp_title', 100, 2 );

function api_title_title(): string {
	return $GLOBALS['api_item_title'] ?? '';
}

function should_rewrite_wp_title(): bool {
	return apply_filters( 'linked_events_is_wp_title_rewrite_enabled', false );
}
