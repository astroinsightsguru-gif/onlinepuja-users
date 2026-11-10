<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Brain\Vault;

class VaultSetCommand extends Command
{
    protected $signature = 'vault:set {key : The setting key (e.g. fb_app_id, fb_app_secret, gemini_key)} {value : The value to store} {--public : Do not encrypt this value}';
    protected $description = 'Set an encrypted setting in the Brain Vault';

    public function handle()
    {
        $key = trim($this->argument('key'));
        $value = trim($this->argument('value'));
        $isSecret = !$this->option('public');

        Vault::set($key, $value, $isSecret);

        $this->info("✅ Successfully saved [{$key}] into Brain Vault (Encrypted: " . ($isSecret ? 'Yes' : 'No') . ").");

        return 0;
    }
}
