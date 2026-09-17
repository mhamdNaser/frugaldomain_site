<?php

namespace App\Modules\Core\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatisticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'status' => 'success',
            'message' => 'Dashboard statistics retrieved successfully',
            'data' => [
                'summary' => [
                    'total_users' => $this['summary']['total_users'] ?? 0,
                    'active_users' => $this['summary']['active_users'] ?? 0,
                    'visits_today' => $this['summary']['visits_today'] ?? 0,
                    'visits_total' => $this['summary']['visits_total'] ?? 0,
                    'visitors_period' => $this['summary']['visitors_period'] ?? 0,
                    'logins_period' => $this['summary']['logins_period'] ?? 0,
                    'icon_interactions_period' => $this['summary']['icon_interactions_period'] ?? 0,
                ],
                'percentages' => [
                    'active_users' => ($this['percentages']['active_users'] ?? 0) . '%',
                    'registered_visitor_share' => ($this['percentages']['registered_visitor_share'] ?? 0) . '%',
                ],
                'charts' => [
                    'users_growth' => $this['charts']['users_growth'] ?? [],
                    'visits_timeline' => $this['charts']['visits_timeline'] ?? [],
                ],
                'last_updated' => $this['last_updated'] ?? null,
            ],
            'metadata' => [
                'api_version' => '1.0',
                'server_time' => now()->toDateTimeString(),
            ],
        ];
    }
}
