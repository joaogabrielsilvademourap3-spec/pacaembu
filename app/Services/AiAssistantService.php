<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;

class AiAssistantService
{
    public function ask(User $user, string $prompt, ?Client $client = null, ?Project $project = null): string
    {
        // Placeholder integration point for OpenAI API client.
        $response = 'AI integration pending environment configuration.';

        AiLog::query()->create([
            'user_id' => $user->id,
            'client_id' => $client?->id,
            'project_id' => $project?->id,
            'prompt' => $prompt,
            'response' => $response,
            'created_at' => now(),
        ]);

        return $response;
    }
}
