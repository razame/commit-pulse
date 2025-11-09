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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchCommitsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user
    ) {}

    public function handle(): void
    {
        try {

            // This job will be handled by the Golang Worker
            // The worker will fetch commits and POST to /api/commits/sync
            
            // Decrypt the GitHub token
            $githubToken = Crypt::decryptString($this->user->github_token);
            
            // Calculate date range (last 7 days)
            $weekAgo = Carbon::now()->subDays(7);
            
            // Get authenticated GitHub user
            $githubUser = $this->getGitHubUser($githubToken);
            if (!$githubUser) {
                Log::error("Failed to get GitHub user for user ID: {$this->user->id}");
                return;
            }
            
            $username = $githubUser['login'];
            
            // Get user's repositories
            $repositories = $this->getRepositories($githubToken, $username);
            
            $syncedCommits = 0;
            
            // Process each repository
            foreach ($repositories as $repo) {
                // Skip archived repositories
                if ($repo['archived'] ?? false) {
                    continue;
                }
                
                // Sync repository
                $repository = Repository::updateOrCreate(
                    [
                        'user_id' => $this->user->id,
                        'repo_name' => $repo['full_name'],
                    ],
                    [
                        'repo_url' => $repo['html_url'],
                        'language' => $repo['language'] ?? null,
                        'last_commit_date' => now(),
                    ]
                );
                
                // Get commits for this repository from the last 7 days
                $commits = $this->getCommits($githubToken, $repo['owner']['login'], $repo['name'], $username, $weekAgo);
                
                // Sync commits
                foreach ($commits as $commit) {
                    $commitDate = Carbon::parse($commit['commit']['author']['date']);
                    
                    // Double-check the date is within our range
                    if ($commitDate->lt($weekAgo)) {
                        continue;
                    }
                    
                    // Get commit stats (additions/deletions)
                    $stats = $this->getCommitStats($githubToken, $repo['owner']['login'], $repo['name'], $commit['sha']);
                    
                    Commit::updateOrCreate(
                        [
                            'user_id' => $this->user->id,
                            'repo_id' => $repository->id,
                            'date' => $commitDate->format('Y-m-d'),
                            'message' => $commit['commit']['message'],
                        ],
                        [
                            'additions' => $stats['additions'] ?? 0,
                            'deletions' => $stats['deletions'] ?? 0,
                            'total_changes' => ($stats['additions'] ?? 0) + ($stats['deletions'] ?? 0),
                        ]
                    );
                    
                    $syncedCommits++;
                }
                
                // Rate limiting - be nice to GitHub API
                usleep(500000); // 0.5 seconds between repositories
            }
            
            // Update user's last_synced_at
            $this->user->update(['last_synced_at' => now()]);
            
            Log::info("Successfully synced {$syncedCommits} commits for user ID: {$this->user->id}");
            
        } catch (\Exception $e) {
            Log::error("Error syncing commits for user ID: {$this->user->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw to mark job as failed
            throw $e;
        }
    }
    
    /**
     * Get authenticated GitHub user
     */
    private function getGitHubUser(string $token): ?array
    {
        try {
            $response = Http::withToken($token)
                ->get('https://api.github.com/user');
            
            if ($response->successful()) {
                return $response->json();
            }
            
            Log::error("GitHub API error getting user", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error("Exception getting GitHub user", ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Get user's repositories
     */
    private function getRepositories(string $token, string $username): array
    {
        $repositories = [];
        $page = 1;
        $perPage = 100;
        
        do {
            try {
                $response = Http::withToken($token)
                    ->get('https://api.github.com/user/repos', [
                        'type' => 'all',
                        'sort' => 'updated',
                        'direction' => 'desc',
                        'per_page' => $perPage,
                        'page' => $page,
                    ]);
                
                if ($response->successful()) {
                    $pageRepos = $response->json();
                    $repositories = array_merge($repositories, $pageRepos);
                    
                    // Check if there are more pages
                    $linkHeader = $response->header('Link');
                    $hasNextPage = $linkHeader && strpos($linkHeader, 'rel="next"') !== false;
                    
                    if (!$hasNextPage || count($pageRepos) < $perPage) {
                        break;
                    }
                    
                    $page++;
                } else {
                    Log::error("GitHub API error getting repositories", [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    break;
                }
                
                // Rate limiting
                usleep(200000); // 0.2 seconds between pages
                
            } catch (\Exception $e) {
                Log::error("Exception getting repositories", ['error' => $e->getMessage()]);
                break;
            }
        } while (true);
        
        return $repositories;
    }
    
    /**
     * Get commits for a repository
     */
    private function getCommits(string $token, string $owner, string $repo, string $author, Carbon $since): array
    {
        $commits = [];
        $page = 1;
        $perPage = 100;
        
        do {
            try {
                $response = Http::withToken($token)
                    ->get("https://api.github.com/repos/{$owner}/{$repo}/commits", [
                        'author' => $author,
                        'since' => $since->toIso8601String(),
                        'per_page' => $perPage,
                        'page' => $page,
                    ]);
                
                if ($response->successful()) {
                    $pageCommits = $response->json();
                    
                    if (empty($pageCommits)) {
                        break;
                    }
                    
                    $commits = array_merge($commits, $pageCommits);
                    
                    // Check if there are more pages
                    $linkHeader = $response->header('Link');
                    $hasNextPage = $linkHeader && strpos($linkHeader, 'rel="next"') !== false;
                    
                    if (!$hasNextPage || count($pageCommits) < $perPage) {
                        break;
                    }
                    
                    $page++;
                } else {
                    // 404 means repo doesn't exist or no access, 422 means no commits
                    if ($response->status() === 404 || $response->status() === 422) {
                        break;
                    }
                    
                    Log::error("GitHub API error getting commits", [
                        'repo' => "{$owner}/{$repo}",
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    break;
                }
                
                // Rate limiting
                usleep(200000); // 0.2 seconds between pages
                
            } catch (\Exception $e) {
                Log::error("Exception getting commits", [
                    'repo' => "{$owner}/{$repo}",
                    'error' => $e->getMessage(),
                ]);
                break;
            }
        } while (true);
        
        return $commits;
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