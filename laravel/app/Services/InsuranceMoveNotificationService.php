<?php

namespace App\Services;

use App\Jobs\InsuranceMoveNotificationEmailJob;
use App\Models\Document;
use App\Models\Enums\InsuranceMoveNotificationChannelEnum;
use App\Models\Enums\InsuranceMoveNotificationStatusEnum;
use App\Models\Financial\Insurance;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

readonly class InsuranceMoveNotificationService
{
    public function __construct(
        private NotificationTemplateService $templateService,
    )
    {
    }

    /**
     * @param Collection<int, Insurance> $insurances
     * @param array<int, int|string> $selectedInsuranceIds
     * @param array<string, string> $channels
     * @param array<string, array{subject?: string, body?: string, send?: bool}> $drafts
     * @param array{line1?: string, line2?: string, zip?: string, city?: string} $oldAddress
     * @param array{line1?: string, line2?: string, zip?: string, city?: string} $newAddress
     */
    public function prepareDrafts(
        Collection $insurances,
        array                                    &$selectedInsuranceIds,
        array                                    &$channels,
        array                                    &$drafts,
        array                                    $oldAddress,
        array                                    $newAddress,
    ): void
    {
        $selected = $insurances
            ->whereIn(Insurance::id, $selectedInsuranceIds)
            ->keyBy(Insurance::id);

        foreach ($selectedInsuranceIds as $insuranceId) {
            $insurance = $selected->get((int)$insuranceId);

            if (!$insurance instanceof Insurance) {
                continue;
            }

            $key = (string)$insurance->id;

            if (!isset($channels[$key]) || $channels[$key] === '') {
                $channels[$key] = InsuranceMoveNotificationChannelEnum::default();
            }

            if (!isset($drafts[$key])) {
                $drafts[$key] = $this->buildDraft(
                    insurance: $insurance,
                    oldAddress: $oldAddress,
                    newAddress: $newAddress,
                    channel: (string)$channels[$key],
                );
            }

            $drafts[$key]['send'] = (bool)($drafts[$key]['send'] ?? true);
        }
    }

    /**
     * @param array<int, int|string> $selectedInsuranceIds
     * @param array<string, string> $channelsByInsuranceId
     * @param array<string, array{subject?: string, body?: string, send?: bool}> $draftsByInsuranceId
     * @param array{line1?: string, line2?: string, zip?: string, city?: string} $oldAddress
     * @return array{processed: int, emailed: int, letters: int, warnings: array<int, string>}
     */
    public function process(
        User  $user,
        array $selectedInsuranceIds,
        array $channelsByInsuranceId,
        array $draftsByInsuranceId,
        array $oldAddress,
    ): array
    {
        $insurances = Insurance::query()
            ->where(Insurance::user_id, $user->id)
            ->whereIn(Insurance::id, $selectedInsuranceIds)
            ->get()
            ->keyBy(Insurance::id);

        $result = [
            'processed' => 0,
            'emailed' => 0,
            'letters' => 0,
            'warnings' => [],
        ];

        foreach ($selectedInsuranceIds as $insuranceId) {
            $insurance = $insurances->get((int)$insuranceId);

            if (!$insurance instanceof Insurance) {
                continue;
            }

            $channel = (string)($channelsByInsuranceId[(string)$insurance->id] ?? InsuranceMoveNotificationChannelEnum::default());
            $draft = $draftsByInsuranceId[(string)$insurance->id] ?? [];
            $shouldSend = (bool)($draft['send'] ?? false);

            $noteParts = [];
            if ($channel === InsuranceMoveNotificationChannelEnum::EMAIL->name && blank($insurance->{Insurance::email})) {
                $channel = InsuranceMoveNotificationChannelEnum::BRIEF->name;
                $noteParts[] = 'E-Mail-Adresse fehlte, Versand auf Brief umgestellt.';
                $result['warnings'][] = sprintf('%s: E-Mail fehlte, daher Brief erstellt.', $insurance->{Insurance::name});
            }

            if (!$shouldSend) {
                $insurance->update([
                    Insurance::move_notified_at => null,
                    Insurance::move_notification_channel => $channel,
                    Insurance::move_notification_status => InsuranceMoveNotificationStatusEnum::PLANNED->name,
                    Insurance::move_notification_note => 'Im Wizard nicht zum Versand ausgewaehlt.',
                ]);

                continue;
            }

            $fallbackDraft = $this->buildDraft(
                insurance: $insurance,
                oldAddress: $oldAddress,
                newAddress: $this->newAddressFromUser($user),
                channel: $channel,
            );

            $subject = trim((string)($draft['subject'] ?? $fallbackDraft['subject']));
            $body = trim((string)($draft['body'] ?? $fallbackDraft['body']));

            try {
                DB::transaction(function () use ($insurance, $user, $channel, $subject, $body, $oldAddress, &$result, $noteParts): void {
                    if ($channel === InsuranceMoveNotificationChannelEnum::EMAIL->name) {
                        InsuranceMoveNotificationEmailJob::dispatch(
                            insuranceId: (int)$insurance->id,
                            recipientEmail: (string)$insurance->{Insurance::email},
                            recipientName: (string)($insurance->{Insurance::company} ?: $insurance->{Insurance::name}),
                            subject: $subject,
                            body: $body,
                        );

                        $result['emailed']++;
                    } else {
                        $document = $this->createLetterDocument($insurance, $user, $subject, $body, $oldAddress);
                        $insurance->{Insurance::has_many_documents}()->save($document);
                        $result['letters']++;
                    }

                    $insurance->update([
                        Insurance::move_notified_at => now(),
                        Insurance::move_notification_channel => $channel,
                        Insurance::move_notification_status => InsuranceMoveNotificationStatusEnum::SENT->name,
                        Insurance::move_notification_note => empty($noteParts) ? null : implode(' ', $noteParts),
                    ]);
                });

                $result['processed']++;
            } catch (Throwable $exception) {
                $insurance->update([
                    Insurance::move_notified_at => null,
                    Insurance::move_notification_channel => $channel,
                    Insurance::move_notification_status => InsuranceMoveNotificationStatusEnum::FAILED->name,
                    Insurance::move_notification_note => sprintf('Fehler: %s', $exception->getMessage()),
                ]);
            }
        }

        return $result;
    }

    /**
     * @param array{line1?: string, line2?: string, zip?: string, city?: string} $oldAddress
     */
    private function createLetterDocument(
        Insurance $insurance,
        User      $user,
        string    $subject,
        string    $body,
        array     $oldAddress,
    ): Document
    {
        $pdfBinary = Pdf::loadView('pdf.insurance-move-notification-letter', [
            'insurance' => $insurance,
            'user' => $user,
            'subject' => $subject,
            'body' => $body,
            'oldAddress' => $oldAddress,
            'newAddress' => $this->newAddressFromUser($user),
            'createdAt' => now(),
        ])->output();

        $filename = sprintf(
            'umzugsmitteilung_%s_%s.pdf',
            $insurance->id,
            now()->format('Ymd_His')
        );

        $path = sprintf('move-notifications/%s/%s', $user->id, $filename);

        Storage::disk(Document::STORAGE_DISK)->put($path, $pdfBinary);

        return Document::query()->create([
            Document::user_id => $user->id,
            Document::type => 'move_notification_letter',
            Document::path => $path,
            Document::filename => $filename,
            Document::description => sprintf('Umzugsmitteilung fuer %s', $insurance->{Insurance::name}),
            Document::sort => 0,
            Document::mime_type => 'application/pdf',
            Document::file_size => strlen($pdfBinary),
        ]);
    }

    /**
     * @return array{line1: string, line2: string, zip: string, city: string}
     */
    public function newAddressFromUser(User $user): array
    {
        $line1 = trim((string)Arr::join(array_filter([
            $user->{User::address_street},
            $user->{User::address_street_number},
        ]), ' '));

        return [
            'line1' => $line1,
            'line2' => '',
            'zip' => (string)($user->{User::address_zip} ?? ''),
            'city' => (string)($user->{User::address_city} ?? ''),
        ];
    }

    /**
     * @param array{line1?: string, line2?: string, zip?: string, city?: string} $oldAddress
     * @param array{line1?: string, line2?: string, zip?: string, city?: string} $newAddress
     * @param string $channel
     * @return array{subject: string, body: string}
     */
    public function buildDraft(Insurance $insurance, array $oldAddress, array $newAddress, string $channel): array
    {
        $template = $this->templateService->getDraftTemplateForChannel($channel);

        $placeholders = [
            'user_name' => (string)auth()->user()?->name,
            'insurance_name' => (string)($insurance->{Insurance::name} ?? ''),
            'insurance_company' => (string)($insurance->{Insurance::company} ?? ''),
            'insurance_number' => (string)($insurance->{Insurance::number} ?? '-'),
            'old_address_line1' => trim((string)($oldAddress['line1'] ?? '')),
            'old_address_line2' => trim((string)($oldAddress['line2'] ?? '')),
            'old_address_zip' => trim((string)($oldAddress['zip'] ?? '')),
            'old_address_city' => trim((string)($oldAddress['city'] ?? '')),
            'new_address_line1' => trim((string)($newAddress['line1'] ?? '')),
            'new_address_line2' => trim((string)($newAddress['line2'] ?? '')),
            'new_address_zip' => trim((string)($newAddress['zip'] ?? '')),
            'new_address_city' => trim((string)($newAddress['city'] ?? '')),
            'current_date' => now()->format('d.m.Y'),
        ];

        return [
            'subject' => $this->templateService->render((string)$template['subject'], $placeholders),
            'body' => $this->templateService->render((string)$template['body'], $placeholders),
        ];
    }
}

