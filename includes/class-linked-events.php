<?php

namespace Evermade\LinkedEvents;

use stdClass;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class for querying hyperin api.
 */
class Linked_Events
{
	protected string $tprek_id;
	protected string $transient_name;
	protected string $api_url;
	protected $default_location;

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
        $response = $this->fetchStores();
		if ( empty( $response->data ) ) {
			return array();
		}

		$enriched_stores = $this->enrichStores( $response->data );

		$next_page_url = $response->meta->next;
		while ( $next_page_url ) {
			$next_response = $this->apiFetch( $next_page_url );

			if ( $next_response->data ) {
				$enriched_stores = array_merge(
					$enriched_stores,
					$this->enrichStores( $next_response->data )
				);
			}

			$next_page_url = $next_response->meta->next;
		}

        $this->cacheStores( $enriched_stores );

		return $enriched_stores;
    }

	protected function enrichStores(array $stores): array
	{
		$enriched_stores = array();
		foreach ( $stores as $store ) {
			if ( $this->inDefaultLocation( $store ) ) {
				if ( ! $this->default_location ) {
					$this->default_location = $this->defaultLocation();
				}

				$store->location = $this->default_location;
			} else {
				$store->location = $this->fetchLocation( $store );
			}

			$enriched_stores[$store->id] = $store;
		}

		return $enriched_stores;
	}

	protected function inDefaultLocation( stdClass $store ): bool
	{
		if ( ! $this->tprekID() ) {
			return false;
		}

		$store_location = (array) $store->location;
		$default_location = 'place/' . $this->tprekID();

		return false !== strpos( $store_location['@id'], $default_location );
	}

	protected function defaultLocation(): ?stdClass
	{
		return $this->tprekID() ? $this->query( 'place/' . $this->tprekID() ) : null;
	}

	protected function fetchLocation(stdClass $store): ?stdClass
	{
        $location = (array) $store->location;

		return ! empty( $location['@id'] ) ? $this->apiFetch( $location['@id'] ) : null;
	}

    /**
     * Return list of stores.
     */
    public function getStores(): array
	{
        $stores = $this->cachedStores();

		return $stores ?: $this->updateStores();
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

	protected function fetchStores(): ?stdClass
	{
		$response = $this->query( 'event', array(
			'location' => $this->tprekID(),
            'start' => 'today',
            'end' => '2090-12-12',
            'sort' => 'start_time',
			'page_size' => 30,
		) );

		return $response ?: null;
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
        $store->location = $this->fetchLocation( $store );

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

		return $this->apiFetch( $query_url );
    }

	protected function apiFetch( string $url )
	{
		$response = wp_remote_retrieve_body( wp_remote_get( $url ) );
        $data = $response ? json_decode( $response ) : false;

		return $data ?: false;
	}

	protected function defaultQueryArgs( string $query_url ): array
	{
		$args = array(
			'format' => 'json',
			'super_event_type' => 'umbrella,none',
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

	protected function cacheStores(array $stores): void
	{
		set_transient( $this->transientName(), $stores, HOUR_IN_SECONDS );
	}

	protected function cachedStores(): array
	{
		$stores = get_transient( $this->transientName() );

		return is_array( $stores ) ? $stores : array();
	}
}
