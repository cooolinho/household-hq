<?php

namespace Tests\Unit;

use App\Jobs\Scheduled\FetchImapDocumentsJob;
use App\Jobs\Scheduled\FixedCostJob;
use App\Jobs\Scheduled\FixedCostTransactionMatchingJob;
use App\Jobs\Scheduled\RecurringTransactionSuggestionDetectionJob;
use App\Jobs\Scheduled\SendUpcomingFixedCostsReminderJob;
use App\Services\ScheduledJobRunner;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ScheduledJobRunnerTest extends TestCase
{
    public function test_it_discovers_all_scheduled_jobs(): void
    {
        $jobs = app(ScheduledJobRunner::class)->all();
        $classes = $jobs->pluck('class')->all();

        self::assertContains(FetchImapDocumentsJob::class, $classes);
        self::assertContains(FixedCostJob::class, $classes);
        self::assertContains(FixedCostTransactionMatchingJob::class, $classes);
        self::assertContains(RecurringTransactionSuggestionDetectionJob::class, $classes);
        self::assertContains(SendUpcomingFixedCostsReminderJob::class, $classes);
    }

    public function test_it_can_run_a_job_by_class_name(): void
    {
        Queue::fake();

        app(ScheduledJobRunner::class)->run(FetchImapDocumentsJob::class);

        Queue::assertPushed(FetchImapDocumentsJob::class);
    }
}

