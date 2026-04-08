<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Laravel\Passport\ClientRepository;

class PassportClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $count = (int) DB::table('oauth_clients')->count();
        if ($count > 0) {
            return;
        }

        $repo = app(ClientRepository::class);
        $client = $repo->createPasswordGrantClient(
            'Seeder Password Grant',
            'users',
            true
        );

        $secret = $client->plainSecret ?? $client->secret ?? '';

        if ($secret === '') {
            $repo->regenerateSecret($client);
            $secret = $client->plainSecret ?? $client->secret ?? '';
        }

        if ($secret === '') {
            throw new \RuntimeException('Passport password client secret is empty after creation.');
        }

        $this->updateEnv(base_path('.env'), $client->id, $secret);
        $this->updateEnv(base_path('docker/.env.backend'), $client->id, $secret);

        Artisan::call('config:clear');
    }

    private function updateEnv(string $path, string $clientId, string $clientSecret): void
    {
        if (! File::exists($path)) {
            return;
        }

        $contents = File::get($path);
        $lines = preg_split("/\\r\\n|\\n|\\r/", $contents);
        $lines = $lines === false ? [] : $lines;

        $foundId = false;
        $foundSecret = false;

        foreach ($lines as $index => $line) {
            if (str_starts_with($line, 'PASSPORT_PASSWORD_CLIENT_ID=')) {
                $lines[$index] = 'PASSPORT_PASSWORD_CLIENT_ID=' . $clientId;
                $foundId = true;
            }
            if (str_starts_with($line, 'PASSPORT_PASSWORD_CLIENT_SECRET=')) {
                $lines[$index] = 'PASSPORT_PASSWORD_CLIENT_SECRET=' . $clientSecret;
                $foundSecret = true;
            }
        }

        if (! $foundId) {
            $lines[] = 'PASSPORT_PASSWORD_CLIENT_ID=' . $clientId;
        }
        if (! $foundSecret) {
            $lines[] = 'PASSPORT_PASSWORD_CLIENT_SECRET=' . $clientSecret;
        }

        File::put($path, implode(PHP_EOL, $lines) . PHP_EOL);
    }
}
