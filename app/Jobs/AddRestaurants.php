<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\Restaurant;
use Illuminate\Bus\Queueable;
use maxh\Nominatim\Nominatim;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Job for adding restaurants to a client asynchronously.
 */
class AddRestaurants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Client $client;
    protected array $restaurants;
    protected int $nbRestaurantsInserted = 0;
    protected int $nbRestaurantsNotInserted = 0;

    /**
     * Create a new job instance.
     *
     * @param \App\Client $client The client to whom the restaurants will be added.
     * @param array $restaurants An array of restaurant data to be added.
     * 
     * @return void
     */
    public function __construct(Client $client, array $restaurants)
    {
        $this->client = $client;
        $this->restaurants = $restaurants;
    }

    /**
     * Get the client associated with the job.
     *
     * @return \App\Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Get the array of restaurant data.
     *
     * @return array
     */
    public function getRestaurants(): array
    {
        return $this->restaurants;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::info('Job started: AddRestaurants');
        Log::info('Adding restaurants for client ' . $this->client->name . ' with ID ' . $this->client->id);

        $nominatim = new Nominatim('http://nominatim.openstreetmap.org/');

        foreach ($this->restaurants as $restaurantData) {
            $this->processRestaurant($restaurantData, $nominatim);
        }

        Log::info('Added ' . $this->nbRestaurantsInserted . ' restaurants');
        Log::info('Skipped ' . $this->nbRestaurantsNotInserted . ' restaurants');

        Log::info('Job completed: AddRestaurants');
    }

    /**
     * Process a restaurant data, check for existence, and add if not present.
     *
     * @param array $restaurantData The data of the restaurant to be processed.
     * @param Nominatim $nominatim The Nominatim instance for geocoding.
     * 
     * @return void
     */
    private function processRestaurant(array $restaurantData, Nominatim $nominatim): void
    {
        Log::info('Processing restaurant ' . $restaurantData['route'] . ' ' . $restaurantData['postal_code'] . ' ' . $restaurantData['city'] . ', ' . $restaurantData['country']);

        $existingRestaurant = $this->findExistingRestaurant($restaurantData);

        if (!$existingRestaurant) {
            $this->processNewRestaurant($restaurantData, $nominatim);
        } else {
            $this->nbRestaurantsNotInserted++;
            Log::warning('A restaurant already exists at the following address: ' . $restaurantData['route'] . ' ' . $restaurantData['postal_code'] . ' ' . $restaurantData['city'] . ', ' . $restaurantData['country']);
        }
    }

    /**
     * Find an existing restaurant based on its data.
     *
     * @param array $restaurantData The data of the restaurant to search for.
     * 
     * @return \App\Models\Restaurant|null
     */
    private function findExistingRestaurant(array $restaurantData): ?Restaurant
    {
        return Restaurant::where([
            'client_id' => $this->client->id,
            'route' => $restaurantData['route'],
            'postal_code' => $restaurantData['postal_code'],
            'city' => $restaurantData['city'],
            'country' => $restaurantData['country']
        ])->first();
    }

    /**
     * Process a new restaurant, perform geocoding, and save it to the database.
     *
     * @param array $restaurantData The data of the new restaurant.
     * @param Nominatim $nominatim The Nominatim instance for geocoding.
     * 
     * @return void
     */
    private function processNewRestaurant(array $restaurantData, Nominatim $nominatim): void
    {
        Log::info('Search geocoding data for ' . $restaurantData['route'] . ' ' . $restaurantData['postal_code'] . ' ' . $restaurantData['city'] . ', ' . $restaurantData['country']);

        $search = $nominatim->newSearch()
            ->country($restaurantData['country'])
            ->city($restaurantData['city'])
            ->postalCode($restaurantData['postal_code'])
            ->street($restaurantData['route'])
            ->addressDetails();

        $geoData = $nominatim->find($search);
        
        if (!empty($geoData) && isset($geoData[0]['lat']) && isset($geoData[0]['lon'])) {
            $latitude = $geoData[0]['lat'];
            $longitude = $geoData[0]['lon'];
        
            $this->createAndSaveRestaurant($restaurantData, $latitude, $longitude);
            $this->nbRestaurantsInserted++;
        } else {
            Log::warning('Geocoding data is missing latitude or longitude for ' . $restaurantData['route'] . ' ' . $restaurantData['postal_code'] . ' ' . $restaurantData['city'] . ', ' . $restaurantData['country']);
            $this->nbRestaurantsNotInserted++;
        }
    }

    /**
     * Create and save a new restaurant instance.
     *
     * @param array $restaurantData The data of the new restaurant.
     * @param string $latitude The latitude of the restaurant.
     * @param string $longitude The longitude of the restaurant.
     * 
     * @return void
     */
    private function createAndSaveRestaurant(array $restaurantData, string $latitude, string $longitude): void
    {
        $restaurant = new Restaurant([
            'route' => $restaurantData['route'],
            'postal_code' => $restaurantData['postal_code'],
            'city' => $restaurantData['city'],
            'country' => $restaurantData['country'],
            'latitude' => $latitude,
            'longitude' => $longitude,
            'client_id' => $this->client->id
        ]);

        $restaurant->client()->associate($this->client);
        $restaurant->save();
        Log::info('Added restaurant with ID ' . $restaurant->id . ' at the following address: ' . $restaurant->route . ' ' . $restaurant->postal_code . ' ' . $restaurant->city . ', ' . $restaurant->country);
    }
}
