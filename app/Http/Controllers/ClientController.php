<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Exports\ClientsExport;
use Maatwebsite\Excel\Facades\Excel;

class ClientController extends Controller
{
    public function index()
    {
        // dd(Client::all());
        return Inertia::render('Client/Index', [
            'clients' => Client::all(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Client/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:clients',
            'company_name' => 'required',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $data['id'] = Str::uuid();
        Client::create($data);
    
        return redirect()->route('client.index')
            ->with('success', 'Client created successfully!');
    }

    public function edit(Client $client)
    {
        // dd($client);
        return Inertia::render('Client/Edit', [
            'client' => $client,
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => 'sometimes|required',
            'email' => 'sometimes|required|email',
            'company_name' => 'sometimes|required',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $client->update($data);

        return redirect()->route('client.index')
            ->with('success', 'Client Edited successfully!');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('client.index')
            ->with('success', 'Client Deteled successfully!');
    }

    public function export()
    {
        return Excel::download(new ClientsExport, 'clients.xlsx');
    }

}
