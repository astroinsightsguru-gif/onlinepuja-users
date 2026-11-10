<?php

namespace App\Console\Commands;

use App\Brain\Vault;
use App\services\BrevoService;
use Illuminate\Console\Command;

/**
 * Shows the Brevo contact lists on the account so an admin can pick the id to
 * put in vault `brevo_list_id` — the audience the publish hook announces to.
 * Purely informational; prints no credentials.
 */
class BrevoListsCommand extends Command
{
    protected $signature = 'brevo:lists';

    protected $description = 'List Brevo contact lists (id, name, subscribers) for choosing brevo_list_id';

    public function handle(BrevoService $brevo): int
    {
        if (! $brevo->isConfigured()) {
            $this->error('Brevo API key is not set in the vault. Set brevo_api_key first.');

            return self::FAILURE;
        }

        $lists = $brevo->lists();

        if (! $lists) {
            $this->warn('No lists returned. Either the account has none yet, or the API key lacks permission.');

            return self::SUCCESS;
        }

        $current = Vault::get('brevo_list_id');

        $this->table(
            ['ID', 'Name', 'Subscribers', 'In use?'],
            array_map(fn ($l) => [
                $l['id'],
                $l['name'],
                $l['subscribers'],
                (string) $l['id'] === $current ? '← current' : '',
            ], $lists)
        );

        $this->line('');
        $this->info('Set the chosen ID as brevo_list_id in Growth & AI OS → AI Engine (vault), or via tinker/vault.');

        return self::SUCCESS;
    }
}
