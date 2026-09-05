<?php

namespace App\Models;

use App\Models\Enums\CustomDashboardWidgetTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomDashboardUserWidget extends Model
{
    const string TABLE = 'custom_dashboard_user_widgets';

    const string id = 'id';

    const string user_id = 'user_id';

    const string title = 'title';

    const string navigation_group = 'navigation_group';

    const string template_key = 'template_key';

    const string widget_type = 'widget_type';

    const string configuration = 'configuration';

    const string width = 'width';

    const string sort = 'sort';

    const string is_active = 'is_active';

    const string created_at = self::CREATED_AT;

    const string updated_at = self::UPDATED_AT;

    const string belongs_to_user = 'user';

    const string WIDTH_SMALL = '1';

    const string WIDTH_HALF = '2';

    const string WIDTH_FULL_GRID = '4';

    const string WIDTH_FULL = 'full';

    protected $table = self::TABLE;

    protected $fillable = [
        self::user_id,
        self::title,
        self::navigation_group,
        self::template_key,
        self::widget_type,
        self::configuration,
        self::width,
        self::sort,
        self::is_active,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, self::user_id);
    }

    /**
     * @param Builder<self> $query
     */
    public function scopeActiveForUser(Builder $query, int $userId): Builder
    {
        return $query
            ->where(self::user_id, $userId)
            ->where(self::is_active, true)
            ->orderBy(self::sort)
            ->orderBy(self::id);
    }

    protected function casts(): array
    {
        return [
            self::widget_type => CustomDashboardWidgetTypeEnum::class,
            self::configuration => 'array',
            self::sort => 'integer',
            self::is_active => 'boolean',
        ];
    }
}
