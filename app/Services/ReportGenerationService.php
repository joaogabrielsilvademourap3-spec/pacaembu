<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Report;
use App\Models\User;

class ReportGenerationService
{
    public function generateForClientMonth(Client $client, string $month, User $generatedBy): Report
    {
        return Report::query()->create([
            'client_id' => $client->id,
            'month' => $month,
            'completed_tasks' => [],
            'published_content' => [],
            'project_progress' => [],
            'metrics_comparison' => [],
            'payments_summary' => [],
            'executive_summary' => 'Summary generation pending AI integration.',
            'generated_by' => $generatedBy->id,
        ]);
    }
}
