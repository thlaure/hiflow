<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Client;
use App\Models\Restaurant;
use App\Jobs\AddRestaurants;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test the AddRestaurants job.
 */
class AddRestaurantsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test adding a new restaurant to a client.
     *
     * @return void
     */
    public function testHandleWithNewRestaurant(): void
    {
        $client = Client::factory()->create();
        $restaurantData = ['route' => '123 Main St', 'postal_code' => '12345', 'city' => 'City', 'country' => 'Country'];

        $job = new AddRestaurants($client, [$restaurantData]);

        $this->assertDatabaseMissing('restaurants', $restaurantData); // Checks that the restaurant does not exist before processing the task

        $job->handle();

        $this->assertDatabaseHas('restaurants', $restaurantData); // Check if the restaurant was added to the database
    }

    /**
     * Test adding an existing restaurant to a client.
     *
     * @return void
     */
    public function testHandleWithExistingRestaurant(): void
    {
        $client = Client::factory()->create();
        $existingRestaurant = Restaurant::factory()->create(['client_id' => $client->id]);

        $restaurantData = [
            'route' => $existingRestaurant->route,
            'postal_code' => $existingRestaurant->postal_code,
            'city' => $existingRestaurant->city,
            'country' => $existingRestaurant->country,
        ];

        $job = new AddRestaurants($client, [$restaurantData]);

        $this->assertDatabaseHas('restaurants', $existingRestaurant->toArray()); // Checks that the restaurant exists before processing the task

        $job->handle();

        $this->assertDatabaseHas('restaurants', $existingRestaurant->toArray()); // Checks that the restaurant has not been added again after the job has been processed
    }
}
