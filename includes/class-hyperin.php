<?php namespace Evermade\LinkedEvents;



/**
 * Class for querying hyperin api.
 */
class LinkedEvents {


    const TRANSIENT_NAME = 'linkedevents-events';


    /**
     * Update stores and save to transiend.
     *
     */
    public function updateStores() {
        // TODO: Change this to the correct location (or allow passing it from outside)...
        $response = $this->query('event', ['location' => 'tprek:51342', 'start' => 'today', 'end' => '2090-12-12', 'sort' => 'start_time'],'',true);
        $stores = [];

        // If we get a response
        if ($response) {
            $stores = $response->data;
        }

        //print_r($stores);

        // Extend with single store information.
        array_walk($stores, function(&$store) {
            $store = $this->getStore($store->id);
        });

        // TODO: Hangle pagination
        // While the response returns a next key in the meta, append the data
        // from the response to $stores and make a new request against that
        // while ( $response->meta->next ) {
        //     array_merge($stores, $response->data);
        //     $results = $this->query($response->meta->next);
        // }

        // Save to cache for an hour.
        update_option($this::TRANSIENT_NAME, json_encode($stores));

    }



    /**
     * Return list of stores.
     *
     * NOTE: Parameters are deprecated. The list is always returned from cache
     * and it always has the full info.
     *
     * @param Boolean $fullInfo DEPRECATED If set to true, full store details are provided. This perform multiple API requests so and the response will be cached.
     * @param Boolean @enableCache DEPRECATED Disable/enable caching.
     */
    public function getStores($fullInfo = false, $enableCache = true) {

        // Get from cache.
        $cachedStores = get_option($this::TRANSIENT_NAME);

        // Cached exists => return.
        if ($cachedStores != false) {
            return json_decode($cachedStores);
        } else {
            return [];
        }

    }



    /**
     * Return single store
     *
     * @param String $storeId Store's id.
     * @return Array Store details.
     */
    public function getStore($storeId) {

        $store = $this->query('event/'.$storeId);

        // print_r($store);
        // die();

        if (!$store) {
            return false;
        }


        // Fetch location information for given store (aka event)
        $locationObject = $store->location;
        $locationURL = (array)$locationObject;
        // Pass in entire URL to query
        $location = $this->query('', [], $locationURL['@id']);
        $store->location = $location;

        // Map images.
        // $store = $store->store;
        // $store->storeOwnerURL = false;
        // $store->storeSignatureURL = false;
        // if (isset($store->tenantImages)) {

        //     $storeOwner = array_filter($store->tenantImages, function($image) {
        //         return count($image->tags) && $image->tags[0] == 'store-owner';
        //     });
        //     $store->storeOwnerURL = $storeOwner ? array_pop($storeOwner)->url : false;

        //     $storeSignature = array_filter($store->tenantImages, function($image) {
        //         return count($image->tags) && $image->tags[0] == 'store-signature';
        //     });
        //     $store->storeSignatureURL = $storeSignature ? array_pop($storeSignature)->url : false;

        // }

        // $store->offers = $this->getOffers($store->id);

        return $store;

    }



    /**
     * Perform API query.
     *
     * @param String $endPoint Endpoint command like 'stores' or 'offers'.
     * @param Array $args Associative array of optional parameters.
     * @param String $overrideURL Optional override to query.
     * @return StdClass representation of the returned json. False on error.
     */
    private function query($endPoint, $args = [], $overrideURL = '') {

        $urlParams = '';
        array_walk($args, function($value, $key) use (&$urlParams) {
            $urlParams .= $key . '=' . $value . '&';
        });


            if ($overrideURL == '') {
                $url = 'https://api.hel.fi/linkedevents/v1/'.$endPoint.'?'.$urlParams.'api_key='.LINKEDEVENTS_APIKEY.'&format=json&page_size=100';
            } else {
                $url = $overrideURL.'?'.$urlParams.'api_key='.LINKEDEVENTS_APIKEY.'&format=json&page_size=100';
            }
            $data = json_decode(file_get_contents($url));



        if ($data) {
            return $data;
        } else {
            return false;
        }

    }



}
