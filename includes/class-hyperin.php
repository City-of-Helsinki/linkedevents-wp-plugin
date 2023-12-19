<?php

namespace Evermade\LinkedEvents;

use stdClass;

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
    public function updateStores(): array
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

        $stores = $response ? $response->data : array();
		$enriched_stores = array();
		foreach ( $stores as $store ) {
			$store = $this->fetchStore($store->id);
			if ( $store ) {
				$enriched_stores[$store->id] = $store;
			}
		}

        // TODO: Handle pagination
        // While the response returns a next key in the meta, append the data
        // from the response to $stores and make a new request against that
        // while ( $response->meta->next ) {
        //     array_merge($stores, $response->data);
        //     $results = $this->query($response->meta->next);
        // }

        // Save to cache for an hour.
		set_transient( $this->transientName(), $enriched_stores, HOUR_IN_SECONDS );

		return $enriched_stores;
    }

    /**
     * Return list of stores.
     */
    public function getStores(): array
	{
        $stores = get_transient( $this->transientName() );
		if ( is_array( $stores ) && $stores ) {
			return $stores;
		}

		return $this->updateStores();
    }

    /**
     * Return single store
     */
    public function getStore( string $storeId ): stdClass
	{
		$stores = $this->getStores();
		if ( ! empty( $stores[$storeId] ) ) {
			return $stores[$storeId];
		}

        $store = $this->fetchStore( $storeId );

		return $store ?: new stdClass();
    }

    /**
     * Return single store
     */
    protected function fetchStore( string $storeId )
	{
        $store = $this->query('event/'.$storeId);
        if ( ! $store ) {
            return;
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

		$response = wp_remote_retrieve_body( wp_remote_get( $query_url ) );
        $data = $response ? json_decode( $response ) : false;

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
