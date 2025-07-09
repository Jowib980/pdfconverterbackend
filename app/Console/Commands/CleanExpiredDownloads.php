<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DownloadToken;
use Illuminate\Support\Facades\Storage;

class CleanExpiredDownloads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-expired-downloads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredTokens = DownloadToken::where('expires_at', '<', now())->get();

        foreach ($expiredTokens as $token) {
            $files = json_decode($token->files, true);

            // Delete actual files from storage
            if ($files && is_array($files)) {
                foreach ($files as $fileUrl) {
                    $relativePath = str_replace(asset('storage') . '/', '', $fileUrl);
                    Storage::disk('public')->delete($relativePath);
                }
            }
            $token->delete();
        }

        $this->info("Expired files and DB entries cleaned.");
    }
}
