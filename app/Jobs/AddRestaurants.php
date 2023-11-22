<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\Restaurant;
use Illuminate\Bus\Queueable;
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
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::info('Job started: AddRestaurants');
        Log::info('Adding restaurants for client ' . $this->client->name . ' with ID ' . $this->client->id);

        $nbRestaurantsInserted = 0;
        $nbRestaurantsNotInserted = 0;
        foreach ($this->restaurants as $restaurant) {
            // Check if the restaurant already exists
            $existingRestaurant = Restaurant::where([
                'client_id' => $this->client->id,
                'route' => $restaurant['route'],
                'postal_code' => $restaurant['postal_code'],
                'city' => $restaurant['city'],
                'country' => $restaurant['country'],
            ])->first();

            if (!$existingRestaurant) {
                // Create a new restaurant and associate it to the client
                $restaurant = new Restaurant($restaurant);
                $restaurant->client()->associate($this->client);
                $restaurant->save();
                
                $nbRestaurantsInserted++;
                Log::info('Added restaurant with ID ' . $restaurant->id . ' at the following address: ' . $restaurant->route . ' ' . $restaurant->postal_code . ' ' . $restaurant->city . ', ' . $restaurant->country);
            } else {
                $nbRestaurantsNotInserted++;
                Log::warning('Restaurant with ID ' . $restaurant->id . ' already exists at the following address: ' . $restaurant->route . ' ' . $restaurant->postal_code . ' ' . $restaurant->city . ', ' . $restaurant->country);
            }
        }

        Log::info('Added ' . $nbRestaurantsInserted . ' restaurants');
        Log::info('Skipped ' . $nbRestaurantsNotInserted . ' restaurants');

        Log::info('Job completed: AddRestaurants');
    }
}
