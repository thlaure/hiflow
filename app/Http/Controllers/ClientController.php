<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Jobs\AddRestaurants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            $name = $request->input('name');
            $client = Client::create([
                'name' => $name,
                'siren' => $siren,
                'contact' => $request->input('contact'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
            ]);

            if (!$client) {
                Log::error('Error creating client with SIREN ' . $siren);
                throw new \Exception('Error creating client with SIREN ' . $siren);
            }

            // Use a job to add the restaurants asynchronously
            if ($request->has('restaurants') && is_array($request->input('restaurants')) && count($request->input('restaurants')) > 0) {
                $uniqueRestaurants = array_unique($request->input('restaurants'), SORT_REGULAR);
                AddRestaurants::dispatch($client, $uniqueRestaurants);
            }

            Log::info("Customer $name with SIREN $siren added successfully.");
            
            return response()->json(['message' => "Customer $name with SIREN $siren added successfully. Restaurants are currently being added."], 201);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
