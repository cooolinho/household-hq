<?php

namespace App\Filament\Admin\Resources\Financial\Insurances\Widgets;

use App\Filament\Admin\Resources\Financial\Insurances\InsuranceResource;
use App\Models\Financial\Insurance;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class InsuranceDashboardWidget extends Widget
{
    public int|string|array $columnSpan = 'full';
    public int $lookaheadDays = 90;

    protected string $view = 'filament.admin.resources.financial.insurances.widgets.insurance-dashboard-widget';

    protected function getViewData(): array
    {
        $today = CarbonImmutable::today();
        $lookaheadEnd = $today->addDays($this->lookaheadDays);
        $userId = auth()->id();

        if (!$userId) {
            return $this->buildDashboardDataFromInsurances(collect(), $today);
        }

        $query = Insurance::query()
            ->where(Insurance::user_id, $userId);

        /** @var Collection<int, Insurance> $insurances */
        $insurances = $query
            ->orderBy(Insurance::end_date)
            ->orderBy(Insurance::name)
            ->get();

        $data = $this->buildDashboardDataFromInsurances($insurances, $today);
        $data['lookaheadEnd'] = $lookaheadEnd;

        return $data;
    }

    /**
     * @param Collection<int, Insurance> $insurances
     * @return array<string, mixed>
     */
    protected function buildDashboardDataFromInsurances(Collection $insurances, CarbonImmutable $today): array
    {
        $lookaheadEnd = $today->addDays($this->lookaheadDays);

        $mapped = $insurances
            ->map(function (Insurance $insurance) use ($today): array {
                $endDate = $this->toImmutableDate($insurance->getAttribute(Insurance::end_date));

                return [
                    'id' => $insurance->getAttribute(Insurance::id),
                    'name' => (string)$insurance->getAttribute(Insurance::name),
                    'company' => (string)($insurance->getAttribute(Insurance::company) ?? ''),
                    'end_date' => $endDate,
                    'days_until_end' => $endDate ? $today->diffInDays($endDate, false) : null,
                    'view_url' => $insurance->getAttribute(Insurance::id)
                        ? InsuranceResource::getViewUrl((int)$insurance->getAttribute(Insurance::id))
                        : null,
                ];
            })
            ->sortBy([
                ['end_date', 'asc'],
                ['name', 'asc'],
            ])
            ->values();

        $expiringSoon = $mapped
            ->filter(fn(array $item): bool => $item['end_date'] instanceof CarbonImmutable
                && $item['end_date']->greaterThanOrEqualTo($today)
                && $item['end_date']->lessThanOrEqualTo($lookaheadEnd))
            ->values();

        $expired = $mapped
            ->filter(fn(array $item): bool => $item['end_date'] instanceof CarbonImmutable
                && $item['end_date']->lessThan($today))
            ->values();

        return [
            'today' => $today,
            'lookaheadDays' => $this->lookaheadDays,
            'lookaheadEnd' => $lookaheadEnd,
            'totalInsurances' => $insurances->count(),
            'withEndDateCount' => $mapped->filter(fn(array $item): bool => $item['end_date'] instanceof CarbonImmutable)->count(),
            'withoutEndDateCount' => $mapped->filter(fn(array $item): bool => !($item['end_date'] instanceof CarbonImmutable))->count(),
            'expiringSoonInsurances' => $expiringSoon,
            'expiringSoonCount' => $expiringSoon->count(),
            'expiredInsurances' => $expired,
            'expiredCount' => $expired->count(),
        ];
    }

    private function toImmutableDate(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof CarbonImmutable) {
            return $value->startOfDay();
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return CarbonImmutable::instance($value)->startOfDay();
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return CarbonImmutable::parse($value)->startOfDay();
    }
}
