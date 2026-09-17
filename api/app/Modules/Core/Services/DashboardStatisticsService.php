<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Repositories\Interfaces\DashboardRepositoryInterface;
use App\Modules\User\Models\User;

/**
 * Platform statistics.
 *
 * Store metrics were removed: the product no longer has a storefront side, so
 * "users with stores" and "stores growth" were reporting a permanent zero.
 * Visitor and engagement figures come from AnalyticsStatisticsService instead.
 */
class DashboardStatisticsService
{
    protected $repository;
    protected AnalyticsStatisticsService $analytics;

    public function __construct(
        DashboardRepositoryInterface $repository,
        AnalyticsStatisticsService $analytics
    ) {
        $this->repository = $repository;
        $this->analytics = $analytics;
    }

    public function getStatistics(): array
    {
        $totalUsers = $this->repository->getTotalUsers();
        $activeUsers = $this->repository->getActiveUsers();
        $overview = $this->analytics->overview(30);

        return [
            'summary' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'visits_today' => $overview['visits']['today'],
                'visits_total' => $overview['visits']['total'],
                'visitors_period' => $overview['visitors']['period'],
                'logins_period' => $overview['logins']['period'],
                'icon_interactions_period' => $overview['icon_interactions']['period'],
            ],
            'percentages' => [
                'active_users' => $this->calculatePercentage($activeUsers, $totalUsers),
                'registered_visitor_share' => $this->calculatePercentage(
                    $overview['registered_visitors'],
                    max(1, $overview['visitors']['period'])
                ),
            ],
            'charts' => [
                'users_growth' => $this->getUsersGrowthLastMonths(6),
                'visits_timeline' => $this->analytics->visitsTimeline(30),
            ],
            'last_updated' => now()->toDateTimeString(),
        ];
    }

    private function calculatePercentage(int $part, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($part / $total) * 100, 2);
    }

    private function getUsersGrowthLastMonths(int $months = 6): array
    {
        $growth = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);

            $count = User::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $growth[] = [
                'month' => $date->format('M Y'),
                'count' => $count,
            ];
        }

        return $growth;
    }
}
