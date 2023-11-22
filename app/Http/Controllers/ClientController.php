<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Jobs\AddRestaurants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * API for managing clients and associated restaurants.
 */
class ClientController extends Controller
{
    /**
     * Retrieve all clients.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Log::info('Request to get all clients.');
        $clients = Client::all();
        return response()->json($clients);
    }

    /**
     * Add a new client.
     *
     * @param  \Illuminate\Http\Request $request
     * 
     * @return \Illuminate\Http\Response
     */
    public function addClient(Request $request)
    {
        try {
            Log::info('Request to add a new client.');

            // Check if the request is valid
            $request->validate([
                'name' => 'required|string|max:255',
                'siren' => 'required|string|max:255|unique:clients,siren',
                'contact' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:clients,email',
                'phone' => 'required|string|max:255',
                'restaurants' => 'array|nullable'
            ]);

            // Check if a client with the same SIREN already exists
            $siren = $request->input('siren');
            $existingClient = Client::where('siren', $siren)->first();
            if ($existingClient) {
                Log::warning("A customer already exists with the SIREN number: $siren");
                return response()->json(['error' => "A customer already exists with the SIREN number: $siren."], 422);
            }

            // Create a new Client
            $client = $this->createClient($request->all());

            if (!$client) {
                Log::error('Error creating client with SIREN ' . $siren);
                throw new \Exception('Error creating client with SIREN ' . $siren);
            }

            // Use a job to add the restaurants asynchronously
            if ($request->has('restaurants') && is_array($request->input('restaurants')) && count($request->input('restaurants')) > 0) {
                $restaurants = $request->input('restaurants');
                $this->addRestaurants($restaurants, $client);
            }

            $name = $client->name;
            Log::info("Customer $name with SIREN $siren added successfully.");
            
            return response()->json(['message' => "Customer $name with SIREN $siren added successfully. Restaurants are currently being added."], 201);
        } catch (ValidationException $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Add restaurants to a client.
     *
     * @param  \Illuminate\Http\Request $request
     * 
     * @return void
     */
    private function addRestaurants(array $restaurants, Client $client): void
    {
        if (is_array($restaurants) && count($restaurants) > 0) {
            $uniqueRestaurants = array_unique($restaurants, SORT_REGULAR);
            AddRestaurants::dispatch($client, $uniqueRestaurants);
        }
    }

    /**
     * Create a new client.
     *
     * @param  array $data
     * 
     * @return \App\Models\Client
     */
    private function createClient(array $data): Client
    {
        return Client::create([
            'name' => $data['name'],
            'siren' => $data['siren'],
            'contact' => $data['contact'],
            'email' => $data['email'],
            'phone' => $data['phone'],
        ]);
    }
}
