<?php

namespace Evermade\LinkedEvents\Api;

use Evermade\LinkedEvents\LinkedEvents;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

add_action( 'init', __NAMESPACE__ . '\\handle_events_and_actions' );
function handle_events_and_actions(): void {
	$cron = apply_filters( 'linked_events_cron_hook_name', '' );
	if ( $cron ) {
		add_action( $cron, __NAMESPACE__ . '\\update_stores' );
	}

	add_action( 'admin_post_' . admin_sync_action(), __NAMESPACE__ . '\\sync_events' );
}

add_filter( 'linked_events_api', __NAMESPACE__ . '\\provide_linked_events_api' );
function provide_linked_events_api(): LinkedEvents
{
	return new LinkedEvents( array(
		'tprek_id' => apply_filters( 'linked_events_tprek_id', '' ),
		'transient_name' => 'linkedevents-events',
		'api_url' => 'https://api.hel.fi/linkedevents/v1',
	) );
}

/**
  * Admin action
  */
function admin_sync_action(): string {
	return 'sync_linked_events';
}

function sync_events(): void {
	update_stores();

	wp_redirect( add_query_arg(
        array( 'page' => apply_filters( 'linked_events_settings_page_slug', '' ) ),
        admin_url( 'admin.php' )
    ) );
	die;
}

add_action( 'linked_events_settings_page', __NAMESPACE__ . '\\sync_events_button', 100 );
function sync_events_button(): void {
	printf(
		'<a class="button" href="%s">%s</a>',
		esc_url( add_query_arg(
	        array( 'action' => admin_sync_action() ),
	        admin_url( 'admin-post.php' )
	    ) ),
		__( 'Sync events', 'linkedevents' )
	);
}

/**
 * Get all stores. Not pagination or any other fancy stuff since there are not that many items.
 *
 * @return void
 */
function update_stores() {
    $hyperIn = apply_filters( 'linked_events_api', null );
	if ( $hyperIn ) {
		$hyperIn->updateStores();

		return rest_ensure_response( array( 'success' => true ) );
	} else {
		return rest_ensure_response( array( 'success' => false ) );
	}
}


/**
 * Get all stores. Not pagination or any other fancy stuff since there are not that many items.
 *
 * @return void
 */
function get_stores() {
	$hyperIn = apply_filters( 'linked_events_api', null );
	if ( ! $hyperIn ) {
		return rest_ensure_response( array() );
	}

    // Get stores.
    $stores = $hyperIn->getStores();
    if ( ! $stores ) {
        return rest_ensure_response( array() );
    }

    // Do some magic to transform API respose to be like WordPress post object.
    $storesWp = [];
    array_walk($stores, function($store) use (&$storesWp) {

        // TODO: Figure out a sane way to do language-based fetches and URLs.
        // Perhaps check WPML language status, and use that to construct this?
        // Name desc location_extra are arrays, need to fetch correct data in frontend
        array_push($storesWp, [
            'ID' => $store->id,
            'post_title' => $store->name,
            'post_content' => $store->description,
            'event_status' => $store->event_status,
            // 'permalink' => get_bloginfo('url') . '/liike/' . $store->id . '/' . sanitize_title($store->name),
            'meta' => array(
                'start_time' => $store->start_time,
                'end_time' => $store->end_time,
                //'last_published_time' => $store->last_published_time,
                'external_links' => $store->external_links ?? '',
                'featured_image_url' => sizeof($store->images) > 0 ? $store->images[0]->url : '',
                'location' => $store->location,
                'location_extra' => $store->location_extra_info,
                'price' => isset($store->offers) && isset($store->offers[0]) && isset($store->offers[0]->price)
                  ? $store->offers[0]->price
                  : '',
                'video' => isset($store->videos) && count($store->videos) > 0
                  ? $store->videos[0]->url
                  : '',
            )
        ]);
    });

	return rest_ensure_response( $storesWp );
}



add_action('rest_api_init', function () {

    register_rest_route( 'linkedevents/v1', '/updateevents', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\\update_stores',
    ));

    register_rest_route( 'linkedevents/v1', '/events', array(
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\\get_stores',
    ));

});
