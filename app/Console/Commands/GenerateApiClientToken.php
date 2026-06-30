<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateApiClientToken extends Command
{
    protected $signature = 'wa:api-client-token
        {name : Client display name}
        {--slug= : Stable client slug}
        {--scope=* : Comma separated scopes}
        {--rate=60 : Rate limit per minute}';

    protected $description = 'Create or rotate a WA Gateway API client bearer token';

    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));
        $slug = $this->option('slug') ?: Str::slug($name);
        $scopeOption = $this->option('scope');
        $scopeText = is_array($scopeOption) ? implode(',', $scopeOption) : (string) $scopeOption;
        $scopes = array_values(array_filter(array_map('trim', explode(',', $scopeText))));

        $rateOption = $this->option('rate');
        $rate = max(1, (int) (is_array($rateOption) ? reset($rateOption) : $rateOption));
        $plainToken = ApiClient::generatePlainToken();

        $client = ApiClient::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'token_hash' => ApiClient::hashToken($plainToken),
                'scopes' => $scopes ?: ['*'],
                'rate_limit_per_minute' => $rate,
                'is_active' => true,
            ]
        );

        $this->info('API client token generated. Copy this token now; only the hash is stored.');
        $this->line('Client ID: ' . $client->id);
        $this->line('Name: ' . $client->name);
        $this->line('Slug: ' . $client->slug);
        $this->line('Scopes: ' . implode(',', $client->scopes ?: []));
        $this->line('Rate/minute: ' . $client->rate_limit_per_minute);
        $this->line('Token: ' . $plainToken);

        return self::SUCCESS;
    }
}
