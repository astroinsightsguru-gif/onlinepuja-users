<?php

// NOTE: lowercase `services` is deliberate. Composer maps PSR-4 "App\" to
// "app/", and this directory is literally `app/services`, so only
// `App\services\…` is autoloadable here — `App\Services\…` silently fails
// to resolve on a case-sensitive filesystem.
namespace App\Services;

use App\Brain\Vault;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Brevo (formerly Sendinblue) — transactional email + contact lists.
 *
 * Credentials live in the encrypted Brain vault rather than systemflag,
 * because the Growth OS already manages its own keys there and this is
 * driven by the blogging pipeline, not the app's operational settings.
 *
 * Verified endpoint: POST https://api.brevo.com/v3/smtp/email
 * Auth: `api-key` request header (NOT a Bearer token).
 */
class BrevoService
{
    protected const BASE = 'https://api.brevo.com/v3';

    /** Brevo accepts many recipients per transactional call; keep batches modest. */
    protected const BATCH = 90;

    public function apiKey(): string
    {
        return Vault::get('brevo_api_key');
    }

    public function isConfigured(): bool
    {
        return $this->apiKey() !== '';
    }

    public function sender(): array
    {
        return [
            'email' => Vault::get('brevo_sender_email', 'care@onlinepuja.live'),
            'name' => Vault::get('brevo_sender_name', 'Onlinepuja.live'),
        ];
    }

    protected function request()
    {
        return Http::withOptions([
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ],
        ])->withHeaders([
            'api-key' => $this->apiKey(),
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ])->timeout(30);
    }

    /**
     * Send one transactional email.
     *
     * @param  array<int,array{email:string,name?:string}>  $to
     * @return array{ok:bool,message_id:?string,error:string}
     */
    public function sendTransactional(array $to, string $subject, string $htmlContent): array
    {
        $fail = fn (string $e) => ['ok' => false, 'message_id' => null, 'error' => $e];

        if (! $this->isConfigured()) {
            return $fail('Brevo API key is not set in the vault.');
        }

        // Brevo rejects the whole call with "name is missing in to" if any
        // recipient lacks a name, and list contacts often have none. Guarantee
        // one — the part before the @ is a sane, non-empty fallback.
        $to = array_values(array_map(
            fn ($r) => [
                'email' => $r['email'],
                'name' => ! empty($r['name']) ? $r['name'] : ucfirst(strtok($r['email'], '@')),
            ],
            array_filter($to, fn ($r) => ! empty($r['email']))
        ));

        if (! $to) {
            return $fail('No recipients.');
        }

        try {
            $res = $this->request()->post(self::BASE.'/smtp/email', [
                'sender' => $this->sender(),
                'to' => $to,
                'subject' => mb_substr($subject, 0, 255),
                'htmlContent' => $htmlContent,
            ]);
        } catch (\Throwable $e) {
            return $fail($e->getMessage());
        }

        if ($res->failed()) {
            // Brevo returns {code, message} — carry it through rather than a
            // bare status, so a bad key reads differently from a bad payload.
            return $fail('HTTP '.$res->status().' :: '.substr($res->body(), 0, 300));
        }

        return ['ok' => true, 'message_id' => $res->json('messageId'), 'error' => ''];
    }

    /**
     * Add (or update) a contact and subscribe it to the announcement list. This
     * is what finally gives the publish notifications an audience — without a
     * capture path every "new article" email goes to nobody. Idempotent:
     * re-subscribing an existing address just refreshes it.
     *
     * @return array{ok:bool,already:bool,error:string}
     */
    public function subscribe(string $email, string $name = '', ?string $listId = null): array
    {
        $fail = fn (string $e) => ['ok' => false, 'already' => false, 'error' => $e];

        if (! $this->isConfigured()) {
            return $fail('Brevo API key is not set.');
        }

        $email = trim(mb_strtolower($email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $fail('Please enter a valid email address.');
        }

        $listId = $listId ?: Vault::get('brevo_list_id');

        if ($listId === '') {
            return $fail('No Brevo list configured (brevo_list_id).');
        }

        $attributes = [];

        if ($name !== '') {
            $parts = preg_split('/\s+/', trim($name), 2);
            $attributes['FIRSTNAME'] = $parts[0];
            $attributes['LASTNAME'] = $parts[1] ?? '';
        }

        try {
            $res = $this->request()->post(self::BASE.'/contacts', array_filter([
                'email' => $email,
                'attributes' => $attributes ?: null,
                'listIds' => [(int) $listId],
                'updateEnabled' => true,   // idempotent — update if already present
            ], fn ($v) => $v !== null));
        } catch (\Throwable $e) {
            return $fail($e->getMessage());
        }

        // 201 created, 204 updated. Brevo returns 400 "Contact already exist"
        // when updateEnabled is false — we set it true, so treat any 2xx as ok.
        if ($res->successful()) {
            return ['ok' => true, 'already' => $res->status() === 204, 'error' => ''];
        }

        $body = (string) $res->body();

        if (str_contains($body, 'already') || str_contains($body, 'duplicate')) {
            return ['ok' => true, 'already' => true, 'error' => ''];
        }

        return $fail('HTTP '.$res->status().' :: '.substr($body, 0, 200));
    }

    /**
     * All contact lists on the account, so an admin can pick which one the
     * publish hook should announce to (its id goes in vault brevo_list_id).
     *
     * @return array<int,array{id:int,name:string,subscribers:int}>
     */
    public function lists(int $cap = 50): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $res = $this->request()->get(self::BASE.'/contacts/lists', ['limit' => $cap, 'offset' => 0]);
        } catch (\Throwable $e) {
            Log::warning('Brevo lists failed: '.$e->getMessage());

            return [];
        }

        if ($res->failed()) {
            Log::warning('Brevo lists HTTP '.$res->status().': '.substr($res->body(), 0, 200));

            return [];
        }

        return array_map(fn ($l) => [
            'id' => (int) ($l['id'] ?? 0),
            'name' => (string) ($l['name'] ?? ''),
            'subscribers' => (int) ($l['totalSubscribers'] ?? 0),
        ], (array) $res->json('lists', []));
    }

    /**
     * Fetch contacts on a list, paging until exhausted or $cap reached.
     *
     * @return array<int,array{email:string,name:string}>
     */
    public function listContacts(string $listId, int $cap = 2000): array
    {
        if (! $this->isConfigured() || $listId === '') {
            return [];
        }

        $out = [];
        $offset = 0;

        while (count($out) < $cap) {
            try {
                $res = $this->request()->get(self::BASE."/contacts/lists/{$listId}/contacts", [
                    'limit' => 500,
                    'offset' => $offset,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Brevo listContacts failed: '.$e->getMessage());
                break;
            }

            if ($res->failed()) {
                Log::warning('Brevo listContacts HTTP '.$res->status().': '.substr($res->body(), 0, 200));
                break;
            }

            $batch = (array) $res->json('contacts', []);

            if (! $batch) {
                break;
            }

            foreach ($batch as $c) {
                if (! empty($c['email'])) {
                    $attrs = $c['attributes'] ?? [];
                    $out[] = [
                        'email' => $c['email'],
                        'name' => trim(($attrs['FIRSTNAME'] ?? '').' '.($attrs['LASTNAME'] ?? '')) ?: '',
                    ];
                }
            }

            if (count($batch) < 500) {
                break;
            }

            $offset += 500;
        }

        return array_slice($out, 0, $cap);
    }

    /**
     * Announce a freshly published article to the configured Brevo list, in
     * batches. Returns a report rather than throwing — a notification failure
     * must never roll back a successful publish.
     *
     * @return array{ok:bool,sent:int,batches:int,error:string}
     */
    public function notifyBlogPublished(string $title, string $url, string $excerpt = '', ?string $imageUrl = null): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'sent' => 0, 'batches' => 0, 'error' => 'Brevo API key not set.'];
        }

        $listId = Vault::get('brevo_list_id');
        $recipients = $listId ? $this->listContacts($listId) : [];

        // Fall back to a single admin address so the hook is still useful
        // before a subscriber list exists.
        if (! $recipients) {
            $fallback = Vault::get('brevo_notify_email');

            if ($fallback === '') {
                return ['ok' => false, 'sent' => 0, 'batches' => 0,
                    'error' => 'No Brevo list contacts and no fallback notify email configured.'];
            }

            $recipients = [['email' => $fallback, 'name' => 'Online Puja']];
        }

        $html = $this->blogEmailHtml($title, $url, $excerpt, $imageUrl);
        $sent = 0;
        $batches = 0;
        $errors = [];

        foreach (array_chunk($recipients, self::BATCH) as $chunk) {
            $res = $this->sendTransactional($chunk, "New on Online Puja: {$title}", $html);
            $batches++;

            if ($res['ok']) {
                $sent += count($chunk);
            } else {
                $errors[] = $res['error'];
            }
        }

        return [
            'ok' => $sent > 0,
            'sent' => $sent,
            'batches' => $batches,
            'error' => $errors ? implode(' | ', array_slice($errors, 0, 2)) : '',
        ];
    }

    protected function blogEmailHtml(string $title, string $url, string $excerpt, ?string $imageUrl): string
    {
        $t = e($title);
        $u = e($url);
        $x = e($excerpt);
        $img = $imageUrl
            ? '<img src="'.e($imageUrl).'" alt="'.$t.'" style="width:100%;max-width:560px;height:auto;border-radius:8px;margin-bottom:20px">'
            : '';

        return <<<HTML
<!doctype html>
<html><body style="margin:0;padding:24px;background:#faf7f2;font-family:Helvetica,Arial,sans-serif;color:#1a1712">
  <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:10px;padding:28px">
    {$img}
    <p style="margin:0 0 8px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#8a7f6d">New article</p>
    <h1 style="margin:0 0 14px;font-size:22px;line-height:1.3;color:#1a1712">{$t}</h1>
    <p style="margin:0 0 22px;font-size:15px;line-height:1.6;color:#5a5245">{$x}</p>
    <a href="{$u}" style="display:inline-block;background:#b8791a;color:#fff;text-decoration:none;padding:12px 22px;border-radius:6px;font-weight:bold;font-size:15px">Read the article</a>
    <p style="margin:26px 0 0;font-size:12px;color:#9a917f">You're receiving this because you subscribed to Online Puja.</p>
  </div>
</body></html>
HTML;
    }
}
