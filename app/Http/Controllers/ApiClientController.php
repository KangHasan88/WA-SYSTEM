<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiClientController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');

        $clients = ApiClient::query()
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('slug')
            ->get();

        return view('api-clients.index', [
            'clients' => $clients,
            'status' => $status,
            'newToken' => session('new_api_token'),
            'newTokenSlug' => session('new_api_token_slug'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/', 'unique:api_clients,slug'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['string', 'in:read:status,send:message,approval:request,approval:read,*'],
            'rate_limit_per_minute' => ['required', 'integer', 'min:1', 'max:600'],
        ]);

        $plainToken = ApiClient::generatePlainToken();

        ApiClient::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'token_hash' => ApiClient::hashToken($plainToken),
            'scopes' => array_values($validated['scopes']),
            'rate_limit_per_minute' => $validated['rate_limit_per_minute'],
            'is_active' => true,
        ]);

        return redirect()
            ->route('api-clients.index')
            ->with('success', 'API client dibuat. Token hanya tampil sekali.')
            ->with('new_api_token', $plainToken)
            ->with('new_api_token_slug', $validated['slug']);
    }

    public function toggle(ApiClient $apiClient)
    {
        $apiClient->forceFill(['is_active' => !$apiClient->is_active])->save();

        return redirect()
            ->route('api-clients.index')
            ->with('success', 'Status API client diperbarui.');
    }

    public function rotate(ApiClient $apiClient)
    {
        $plainToken = ApiClient::generatePlainToken();

        $apiClient->forceFill([
            'token_hash' => ApiClient::hashToken($plainToken),
            'is_active' => true,
        ])->save();

        return redirect()
            ->route('api-clients.index')
            ->with('success', 'Token baru dibuat. Token lama langsung tidak berlaku.')
            ->with('new_api_token', $plainToken)
            ->with('new_api_token_slug', $apiClient->slug);
    }
}
