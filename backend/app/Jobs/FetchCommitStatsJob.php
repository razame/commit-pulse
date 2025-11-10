<?php

namespace App\Jobs;

use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchCommitStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Repository $repository,
        public string $commitSha,
        public int $commitId
    ) {}

    public function handle(): void
    {
        try {
            // Decrypt the GitHub token
            $githubToken = Crypt::decryptString($this->user->github_token);
            
            // Parse repository name to get owner and repo name
            $repoParts = explode('/', $this->repository->repo_name);
            if (count($repoParts) !== 2) {
                Log::error("Invalid repository name format: {$this->repository->repo_name}");
                return;
            }
            
            $owner = $repoParts[0];
            $repo = $repoParts[1];
            
            // Get commit statistics (additions/deletions)
            $stats = $this->getCommitStats($githubToken, $owner, $repo, $this->commitSha);
            
            // Update commit record with stats
            $commit = Commit::find($this->commitId);
            if ($commit) {
                $commit->update([
                    'additions' => $stats['additions'] ?? 0,
                    'deletions' => $stats['deletions'] ?? 0,
                    'total_changes' => ($stats['additions'] ?? 0) + ($stats['deletions'] ?? 0),
                ]);
            } else {
                Log::warning("Commit not found for ID: {$this->commitId}");
            }
            
        } catch (\Exception $e) {
            Log::error("Error fetching commit stats for commit ID: {$this->commitId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw to mark job as failed
            throw $e;
        }
    }
    
    /**
     * Get commit statistics (additions/deletions)
     */
    private function getCommitStats(string $token, string $owner, string $repo, string $sha): array
    {
        try {
            $response = Http::withToken($token)
                ->get("https://api.github.com/repos/{$owner}/{$repo}/commits/{$sha}");
            
            if ($response->successful()) {
                $commitData = $response->json();
                $stats = $commitData['stats'] ?? [];
                
                return [
                    'additions' => $stats['additions'] ?? 0,
                    'deletions' => $stats['deletions'] ?? 0,
                ];
            }
            
            // If we can't get stats, return zeros
            return ['additions' => 0, 'deletions' => 0];
            
        } catch (\Exception $e) {
            Log::warning("Could not get commit stats", [
                'repo' => "{$owner}/{$repo}",
                'sha' => $sha,
                'error' => $e->getMessage(),
            ]);
            
            return ['additions' => 0, 'deletions' => 0];
        }
    }
}

