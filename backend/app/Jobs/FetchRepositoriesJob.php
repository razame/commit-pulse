<?php

namespace App\Jobs;

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

class FetchRepositoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user
    ) {}

    public function handle(): void
    {
        try {
            // Decrypt the GitHub token
            $githubToken = Crypt::decryptString($this->user->github_token);
            
            // Get authenticated GitHub user
            $githubUser = $this->getGitHubUser($githubToken);
            if (!$githubUser) {
                Log::error("Failed to get GitHub user for user ID: {$this->user->id}");
                return;
            }
            
            $username = $githubUser['login'];
            
            // Get user's repositories
            $repositories = $this->getRepositories($githubToken, $username);
            
            $syncedRepos = 0;
            
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
                
                // Dispatch job to fetch commits for this repository
                FetchCommitsJob::dispatch($this->user, $repository);
                
                $syncedRepos++;
            }
            
            // Update user's last_synced_at
            $this->user->update(['last_synced_at' => now()]);
            
            Log::info("Successfully synced {$syncedRepos} repositories for user ID: {$this->user->id}. Dispatched jobs to fetch commits.");
            
        } catch (\Exception $e) {
            Log::error("Error syncing repositories for user ID: {$this->user->id}", [
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

                    Log::info(json_encode($response->json()));
                
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
}

