<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;

class ClientController extends Controller
{
    /**
     * Retrieve all clients.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
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
        // Check if the request is valid
        $request->validate([
            'name' => 'required|string|max:255',
            'siren' => 'required|string|max:255|unique:clients,siren',
            'contact' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:clients,email',
            'phone' => 'required|string|max:255'
        ]);

        // Check if a client with the same SIREN already exists
        $existingClient = Client::where('siren', $request->input('siren'))->first();
        if ($existingClient) {
            return response()->json(['error' => 'A customer with the same SIREN number already exists.'], 422);
        }

        // Create a new Client
        $client = Client::create([
            'name' => $request->input('name'),
            'siren' => $request->input('siren'),
            'contact' => $request->input('contact'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
        ]);

        return response()->json(['message' => 'Customer added successfully. Restaurants are currently being added.'], 201);
    }
}
