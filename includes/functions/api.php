<?php namespace Evermade\LinkedEvents\Api;
/**
 * Hooks for the rest API.
 */



/**
 * Get all stores. Not pagination or any other fancy stuff since there are not that many items.
 *
 * @return void
 */
function updateStores() {

    // Get stores.
    $hyperIn = new \Evermade\LinkedEvents\LinkedEvents();
    $stores = $hyperIn->updateStores();

    return ['success' => true];

}


/**
 * Get all stores. Not pagination or any other fancy stuff since there are not that many items.
 *
 * @return void
 */
function getStores() {

    // Get stores.
    $hyperIn = new \Evermade\LinkedEvents\LinkedEvents();
    $stores = $hyperIn->getStores(true);

    // print_r($stores);exit;

    if (!$stores) {
        return [];
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
    return $storesWp;

}



add_action('rest_api_init', function () {

    register_rest_route( 'linkedevents/v1', '/updateevents', array(
        'methods' => 'GET',
        'callback' => '\Evermade\LinkedEvents\Api\updateStores',
    ));

    register_rest_route( 'linkedevents/v1', '/events', array(
        'methods' => 'GET',
        'callback' => '\Evermade\LinkedEvents\Api\getStores',
    ));

});
