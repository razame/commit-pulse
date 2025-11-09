<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CommitsController extends Controller
{
    public function sync(Request $request)
    {
        // Verify worker API key
        $apiKey = $request->header('X-Worker-API-Key');
        if ($apiKey !== config('services.worker.api_key')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'repositories' => 'required|array',
            'repositories.*.name' => 'required|string',
            'repositories.*.url' => 'required|url',
            'repositories.*.language' => 'nullable|string',
            'commits' => 'required|array',
            'commits.*.repo_name' => 'required|string',
            'commits.*.date' => 'required|date',
            'commits.*.message' => 'required|string',
            'commits.*.additions' => 'required|integer',
            'commits.*.deletions' => 'required|integer',
        ]);

        $user = User::findOrFail($data['user_id']);

        // Sync repositories
        foreach ($data['repositories'] as $repoData) {
            Repository::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'repo_name' => $repoData['name'],
                ],
                [
                    'repo_url' => $repoData['url'],
                    'language' => $repoData['language'] ?? null,
                    'last_commit_date' => now(),
                ]
            );
        }

        // Sync commits
        foreach ($data['commits'] as $commitData) {
            $repo = Repository::where('user_id', $user->id)
                ->where('repo_name', $commitData['repo_name'])
                ->first();

            if ($repo) {
                Commit::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'repo_id' => $repo->id,
                        'date' => $commitData['date'],
                        'message' => $commitData['message'],
                    ],
                    [
                        'additions' => $commitData['additions'],
                        'deletions' => $commitData['deletions'],
                        'total_changes' => $commitData['additions'] + $commitData['deletions'],
                    ]
                );
            }
        }

        // Update user's last_synced_at
        $user->update(['last_synced_at' => now()]);

        return response()->json([
            'message' => 'Commits synced successfully',
            'commits_count' => count($data['commits']),
        ]);
    }
}

