<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Client;
use App\Jobs\AddRestaurants;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test retrieving all clients.
     *
     * @return void
     */
    public function testIndex(): void
    {
        Client::factory()->count(3)->create();

        $response = $this->json('GET', '/api/clients');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    /**
     * Test adding a new client and test if the restaurants are added asynchronously.
     *
     * @return void
     */
    public function testAddClientWithRestaurants(): void
    {
        Queue::fake();

        $data = [
            'name' => 'Client Test',
            'siren' => '123456789',
            'contact' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '123-456-7890',
            'restaurants' => [
                ['name' => 'Restaurant 1', 'route' => '123 Main St', 'postal_code' => '12345', 'city' => 'City', 'country' => 'Country'],
            ],
        ];

        $response = $this->json('POST', '/api/clients', $data);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Customer Client Test with SIREN 123456789 added successfully. Restaurants are currently being added.']);

        Queue::assertPushed(AddRestaurants::class, function ($job) use ($data) {
            return $job->getClient()->name === $data['name'] && count($job->getRestaurants()) === count($data['restaurants']);
        });
    }

    /**
     * Test that an error is returned when adding a client with an existing SIREN.
     *
     * @return void
     */
    public function testAddClientWithExistingClient(): void
    {
        $existingClient = Client::factory()->create();

        // Prepare data with an existing SIREN
        $data = [
            'name' => 'New Client',
            'siren' => $existingClient->siren,
            'contact' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '123-456-7890',
            'restaurants' => [
                ['name' => 'Restaurant 1', 'route' => '123 Main St', 'postal_code' => '12345', 'city' => 'City', 'country' => 'Country'],
            ],
        ];

        $response = $this->json('POST', '/api/clients', $data);

        $response->assertJson(['error' => 'The siren has already been taken.'])
            ->assertStatus(422);
        }
}
