<?php

namespace App\Services\EnergyTracker;

use App\Models\EnergyTracker\MeasurementDevice;
use App\Models\EnergyTracker\ReadingEntry;
use App\Models\Enums\EnergyTrackerCountingMethodEnum;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MeasurementDeviceStatisticsService
{
    private const int CACHE_TTL = 86400;
    private const int FORECAST_MONTHS = 3;

    /**
     * @return array{
     *     total_entries: int,
     *     latest_reading_value: ?float,
     *     latest_reading_date: ?string,
     *     latest_consumption: ?float,
     *     latest_interval_days: ?int,
     *     average_daily_consumption_30d: ?float,
     *     average_daily_consumption_90d: ?float,
     *     consumption_30d: float,
     *     consumption_90d: float,
     *     consumption_365d: float,
     *     forecast_30d: ?float,
     *     forecast_next_reading_date: ?string,
     *     average_interval_days: ?float,
     *     data_points_last_90d: int
     * }
     */
    public function getOverview(MeasurementDevice $device): array
    {
        return $this->remember('overview', $device, fn() => $this->computeOverview($device));
    }

    private function remember(string $key, MeasurementDevice $device, callable $callback, array $parts = []): array
    {
        return Cache::remember(
            $this->cacheKey($key, $device, $parts),
            self::CACHE_TTL,
            $callback,
        );
    }

    private function cacheKey(string $key, MeasurementDevice $device, array $parts = []): string
    {
        $latestReadingUpdate = $device->readingEntries()->max(ReadingEntry::updated_at);
        $readingTimestamp = $latestReadingUpdate ? CarbonImmutable::parse($latestReadingUpdate)->timestamp : 0;
        $deviceTimestamp = $device->updated_at?->timestamp ?? 0;
        $stamp = max($deviceTimestamp, $readingTimestamp);

        return 'measurement_device_stats.' . $key . '.' . $device->getKey() . '.' . implode('.', $parts) . '.' . $stamp;
    }

    private function computeOverview(MeasurementDevice $device): array
    {
        $series = $this->getSeries($device);
        $latest = $series->last();
        $last90Days = CarbonImmutable::today()->subDays(90);
        $last30Days = CarbonImmutable::today()->subDays(30);
        $last365Days = CarbonImmutable::today()->subDays(365);

        $consumption30d = $this->sumConsumptionSince($series, $last30Days);
        $consumption90d = $this->sumConsumptionSince($series, $last90Days);
        $consumption365d = $this->sumConsumptionSince($series, $last365Days);
        $avg30d = $this->averageDailyConsumptionSince($series, $last30Days);
        $avg90d = $this->averageDailyConsumptionSince($series, $last90Days);
        $averageInterval = $this->averageIntervalDays($series);

        return [
            'total_entries' => $series->count(),
            'latest_reading_value' => $latest['value'] ?? null,
            'latest_reading_date' => isset($latest['date']) ? $latest['date']->toDateString() : null,
            'latest_consumption' => $latest['consumption'] ?? null,
            'latest_interval_days' => $latest['interval_days'] ?? null,
            'average_daily_consumption_30d' => $avg30d,
            'average_daily_consumption_90d' => $avg90d,
            'consumption_30d' => $consumption30d,
            'consumption_90d' => $consumption90d,
            'consumption_365d' => $consumption365d,
            'forecast_30d' => $avg90d !== null ? round($avg90d * 30, 2) : null,
            'forecast_next_reading_date' => $averageInterval !== null
                ? CarbonImmutable::today()->addDays((int)round($averageInterval))->toDateString()
                : null,
            'average_interval_days' => $averageInterval,
            'data_points_last_90d' => $series->filter(
                fn(array $point): bool => $point['date']->greaterThanOrEqualTo($last90Days)
            )->count(),
        ];
    }

    /**
     * @return Collection<int, array{
     *     date: CarbonImmutable,
     *     value: float,
     *     consumption: ?float,
     *     interval_days: ?int
     * }>
     */
    private function getSeries(MeasurementDevice $device): Collection
    {
        /** @var Collection<int, ReadingEntry> $entries */
        $entries = $device->readingEntries()
            ->orderBy(ReadingEntry::reading_date)
            ->get([ReadingEntry::reading_value, ReadingEntry::reading_date]);

        $previousValue = $device->meter_reading_value;
        $previousDate = $device->meter_reading_date ? CarbonImmutable::parse($device->meter_reading_date) : null;
        $method = EnergyTrackerCountingMethodEnum::tryFromName($device->counting_method);

        return $entries->map(function (ReadingEntry $entry) use (&$previousValue, &$previousDate, $method): array {
            $date = CarbonImmutable::parse($entry->{ReadingEntry::reading_date});
            $value = (float)$entry->{ReadingEntry::reading_value};

            $consumption = null;
            $intervalDays = null;

            if ($previousValue !== null && $previousDate !== null) {
                $rawDelta = match ($method) {
                    EnergyTrackerCountingMethodEnum::DESCENDING => (float)$previousValue - $value,
                    EnergyTrackerCountingMethodEnum::FLUCTUATING => abs($value - (float)$previousValue),
                    default => $value - (float)$previousValue,
                };

                $consumption = max(0, $rawDelta);
                $intervalDays = max(1, $previousDate->diffInDays($date));
            }

            $previousValue = $value;
            $previousDate = $date;

            return [
                'date' => $date,
                'value' => $value,
                'consumption' => $consumption,
                'interval_days' => $intervalDays,
            ];
        })->values();
    }

    private function sumConsumptionSince(Collection $series, CarbonImmutable $from): float
    {
        return round($series
            ->filter(fn(array $point): bool => $point['consumption'] !== null && $point['date']->greaterThanOrEqualTo($from))
            ->sum('consumption'), 2);
    }

    private function averageDailyConsumptionSince(Collection $series, CarbonImmutable $from): ?float
    {
        $filtered = $series->filter(
            fn(array $point): bool => $point['consumption'] !== null && $point['date']->greaterThanOrEqualTo($from)
        );

        $consumption = (float)$filtered->sum('consumption');
        $days = (int)$filtered->sum('interval_days');

        if ($consumption <= 0 || $days <= 0) {
            return null;
        }

        return round($consumption / $days, 4);
    }

    private function averageIntervalDays(Collection $series): ?float
    {
        $intervals = $series
            ->pluck('interval_days')
            ->filter(fn($value): bool => $value !== null)
            ->values();

        if ($intervals->isEmpty()) {
            return null;
        }

        return round($intervals->avg(), 1);
    }

    /**
     * @return array{labels: array<int, string>, actual: array<int, float>, forecast: array<int, float|null>}
     */
    public function getMonthlyConsumptionTrend(MeasurementDevice $device, int $months = 12): array
    {
        return $this->remember('monthly_trend', $device, fn() => $this->computeMonthlyConsumptionTrend($device, $months), [$months]);
    }

    private function computeMonthlyConsumptionTrend(MeasurementDevice $device, int $months): array
    {
        $series = $this->getSeries($device);
        $start = CarbonImmutable::today()->startOfMonth()->subMonths($months - 1);

        $buckets = [];
        for ($cursor = $start; $cursor->lessThanOrEqualTo(CarbonImmutable::today()->startOfMonth()); $cursor = $cursor->addMonth()) {
            $buckets[$cursor->format('Y-m')] = 0.0;
        }

        foreach ($series as $point) {
            if ($point['consumption'] === null) {
                continue;
            }

            $monthKey = $point['date']->startOfMonth()->format('Y-m');
            if (array_key_exists($monthKey, $buckets)) {
                $buckets[$monthKey] += $point['consumption'];
            }
        }

        $avg90d = $this->averageDailyConsumptionSince($series, CarbonImmutable::today()->subMonths(self::FORECAST_MONTHS));
        $forecastValue = $avg90d !== null
            ? round($avg90d * CarbonImmutable::today()->daysInMonth, 2)
            : null;

        $labels = [];
        $actual = [];
        foreach ($buckets as $key => $value) {
            $labels[] = CarbonImmutable::createFromFormat('Y-m', $key)->format('M Y');
            $actual[] = round($value, 2);
        }

        $forecast = array_fill(0, max(0, count($labels) - 1), null);
        $forecast[] = $forecastValue;

        return [
            'labels' => $labels,
            'actual' => $actual,
            'forecast' => $forecast,
        ];
    }

    /**
     * @return array{labels: array<int, string>, datasets: array<int, array{label: string, data: array<int, float>}>}
     */
    public function getYearlyRecentQuarterComparison(MeasurementDevice $device, int $years = 4): array
    {
        return $this->remember('recent_quarter_comparison', $device, fn() => $this->computeYearlyRecentQuarterComparison($device, $years), [$years]);
    }

    private function computeYearlyRecentQuarterComparison(MeasurementDevice $device, int $years): array
    {
        $series = $this->getSeries($device);
        $labels = [];
        $monthStarts = [];

        for ($offset = 2; $offset >= 0; $offset--) {
            $month = CarbonImmutable::today()->startOfMonth()->subMonths($offset);
            $labels[] = $month->format('M Y');
            $monthStarts[] = $month;
        }

        $datasets = [];
        $currentYear = CarbonImmutable::today()->year;

        for ($yearOffset = $years - 1; $yearOffset >= 0; $yearOffset--) {
            $targetYear = $currentYear - $yearOffset;
            $data = [];

            foreach ($monthStarts as $monthStart) {
                $targetMonth = $monthStart->setYear($targetYear);
                $data[] = round($this->sumConsumptionBetween($series, $targetMonth, $targetMonth->endOfMonth()), 2);
            }

            $datasets[] = [
                'label' => (string)$targetYear,
                'data' => $data,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    private function sumConsumptionBetween(Collection $series, CarbonImmutable $from, CarbonImmutable $to): float
    {
        return round($series
            ->filter(fn(array $point): bool => $point['consumption'] !== null
                && $point['date']->greaterThanOrEqualTo($from)
                && $point['date']->lessThanOrEqualTo($to))
            ->sum('consumption'), 2);
    }

    /**
     * @return array{
     *     labels: array<int, string>,
     *     intervals: array<int, int>,
     *     readings: array<int, float>,
     *     average_interval_days: ?float
     * }
     */
    public function getReadingIntervals(MeasurementDevice $device, int $limit = 12): array
    {
        return $this->remember('reading_intervals', $device, fn() => $this->computeReadingIntervals($device, $limit), [$limit]);
    }

    private function computeReadingIntervals(MeasurementDevice $device, int $limit): array
    {
        $series = $this->getSeries($device)
            ->filter(fn(array $point): bool => $point['interval_days'] !== null)
            ->take(-$limit)
            ->values();

        return [
            'labels' => $series->map(fn(array $point): string => $point['date']->format('d.m.Y'))->all(),
            'intervals' => $series->map(fn(array $point): int => (int)$point['interval_days'])->all(),
            'readings' => $series->map(fn(array $point): float => round($point['value'], 2))->all(),
            'average_interval_days' => $this->averageIntervalDays($series),
        ];
    }

    public function refreshCache(MeasurementDevice $device): void
    {
        foreach ([6, 12, 24] as $months) {
            Cache::put(
                $this->cacheKey('monthly_trend', $device, [$months]),
                $this->computeMonthlyConsumptionTrend($device, $months),
                self::CACHE_TTL,
            );
        }

        foreach ([4, 6] as $years) {
            Cache::put(
                $this->cacheKey('recent_quarter_comparison', $device, [$years]),
                $this->computeYearlyRecentQuarterComparison($device, $years),
                self::CACHE_TTL,
            );
        }

        foreach ([12, 24] as $limit) {
            Cache::put(
                $this->cacheKey('reading_intervals', $device, [$limit]),
                $this->computeReadingIntervals($device, $limit),
                self::CACHE_TTL,
            );
        }

        Cache::put(
            $this->cacheKey('overview', $device),
            $this->computeOverview($device),
            self::CACHE_TTL,
        );
    }
}
