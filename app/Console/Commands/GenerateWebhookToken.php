<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateWebhookToken extends Command
{
    protected $signature = 'wa:webhook-token {--show-env : Print WA_WEBHOOK_TOKEN env line}';
    protected $description = 'Generate a random WA webhook token for .env rotation';

    public function handle(): int
    {
        $token = 'wwh_' . Str::random(64);

        $this->warn('Copy this token now. It is not stored by this command.');

        if ($this->option('show-env')) {
            $this->line('WA_WEBHOOK_TOKEN=' . $token);
        } else {
            $this->line($token);
        }

        return self::SUCCESS;
    }
}
