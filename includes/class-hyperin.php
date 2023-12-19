<?php

namespace Evermade\LinkedEvents;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class for querying hyperin api.
 */
class LinkedEvents
{
	protected string $tprek_id;
	protected string $transient_name;
	protected string $api_url;

	public function __construct( array $config )
	{
		$this->tprek_id = $config['tprek_id'] ?? '';
		$this->transient_name = $config['transient_name'] ?? '';
		$this->api_url = trailingslashit($config['api_url'] ?? '');
	}

    protected function tprekID(): string
	{
		return $this->tprek_id;
    }

    protected function transientName(): string
	{
		return $this->transient_name;
    }

    protected function apiUrl(): string
	{
		return $this->api_url;
    }

    /**
     * Update stores and save to transient.
     *
     */
    public function updateStores()
	{
        $response = $this->query(
          'event',
          [
            'location' => $this->tprekID(),
            'start' => 'today',
            'end' => '2090-12-12',
            'sort' => 'start_time'
          ],
          '',
          true
        );

        $stores = [];

        // If we get a response
        if ($response) {
            $stores = $response->data;
        }

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
        update_option( $this->transientName(), json_encode($stores) );
    }

    /**
     * Return list of stores.
     */
    public function getStores()
	{
        $cachedStores = get_option( $this->transientName() );

		return $cachedStores != false ? json_decode( $cachedStores ) : array();
    }

    /**
     * Return single store
     *
     * @param String $storeId Store's id.
     * @return Array Store details.
     */
    public function getStore($storeId)
	{
        $store = $this->query('event/'.$storeId);

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
    private function query($endPoint, $args = [], $overrideURL = '')
	{
		$query_url = $overrideURL ?: $this->apiUrl() . $endPoint;
		$query_url .= '?' . http_build_query(
			array_merge( $this->defaultQueryArgs( $query_url ), $args )
		);

		error_log($query_url);

        $data = json_decode( file_get_contents( $query_url ) );

		return $data ?: false;
    }

	protected function defaultQueryArgs( string $query_url ): array
	{
		$args = array(
			'format' => 'json',
			'page_size' => 100,
		);

		$api_key = apply_filters(
			'linked_events_api_key',
			defined( 'LINKEDEVENTS_APIKEY' ) ? LINKEDEVENTS_APIKEY : ''
		);

		if ( $api_key ) {
			$args['api_key'] = $api_key;
		}

		return apply_filters(
			'linked_events_query_default_args',
			$args,
			$query_url
		);
	}
}
