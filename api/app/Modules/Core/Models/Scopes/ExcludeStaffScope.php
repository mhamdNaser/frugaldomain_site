<?php

namespace App\Modules\Core\Models\Scopes;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Keeps the site's own staff out of every analytics report.
 *
 * Two rules, because each misses something the other catches:
 *  - rows signed by an admin account (user_id), including rows recorded
 *    before staff were excluded at write time;
 *  - rows from a browser known to be an admin's (analytics_excluded_sessions),
 *    which covers the pages an admin opens while signed out.
 *
 * New staff traffic is already dropped by AnalyticsRecorder before it is
 * written; this scope is what cleans up the history.
 */
class ExcludeStaffScope implements Scope
{
    public const STAFF_ROLES = ['admin', 'super-admin'];

    public function __construct(private readonly bool $bySession = true)
    {
    }

    public function apply(Builder $builder, Model $model): void
    {
        $table = $model->getTable();

        $builder->where(function (Builder $query) use ($table) {
            $query->whereNull("{$table}.user_id")
                ->orWhereNotIn("{$table}.user_id", self::staffUserIds());
        });

        if ($this->bySession) {
            $builder->where(function (Builder $query) use ($table) {
                $query->whereNull("{$table}.session_id")
                    ->orWhereNotIn("{$table}.session_id", function ($sub) {
                        $sub->select('session_id')->from('analytics_excluded_sessions');
                    });
            });
        }
    }

    /** A subquery of the ids of every account holding a staff role. */
    public static function staffUserIds(): \Closure
    {
        return function ($sub) {
            $sub->select('model_has_roles.model_id')
                ->from('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->whereIn('roles.name', self::STAFF_ROLES)
                ->where('model_has_roles.model_type', (new User())->getMorphClass());
        };
    }
}
