<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class WorkerController extends Controller
{
    public function getUsers(Request $request)
    {
        // Verify worker API key
        $apiKey = $request->header('X-Worker-API-Key');
        if ($apiKey !== config('services.worker.api_key')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $users = User::whereNotNull('github_token')
            ->where('github_token', '!=', '')
            ->get()
            ->map(function ($user) {
                try {
                    return [
                        'id' => $user->id,
                        'github_id' => $user->github_id,
                        'github_token' => Crypt::decryptString($user->github_token),
                        'last_synced_at' => $user->last_synced_at?->toIso8601String(),
                    ];
                } catch (\Exception $e) {
                    // Skip users with invalid tokens
                    return null;
                }
            })
            ->filter();

        return response()->json($users->values());
    }
}

