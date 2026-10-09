<?php

namespace App\Filament\App\Pages;

use App\AppConfig;
use App\Exceptions\TransactionsImportException;
use App\Filament\App\Resources\Financial\BankAccounts\BankAccountResource;
use App\Menu\NavigationGroup;
use App\Models\Financial\BankAccount;
use App\Models\Financial\CSVImportProfile;
use App\Services\TransactionImport\AmountFormat;
use App\Services\TransactionImport\CsvAnalysis;
use App\Services\TransactionImport\CsvAnalyzer;
use App\Services\TransactionImport\CsvEncoding;
use App\Services\TransactionImport\CsvFormat;
use App\Services\TransactionImport\ImportConfiguration;
use App\Services\TransactionImport\ImportReport;
use App\Services\TransactionImport\MappingResolver;
use App\Services\TransactionImport\TransactionImportField;
use App\Services\TransactionImport\TransactionImportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

/**
 * Wizard für den CSV-Import von Transaktionen: Datei & Format → Vorschau → Zuordnung → Prüfung → Ergebnis.
 *
 * Die Seite sammelt nur Eingaben und zeigt Ergebnisse an; Analyse, Mapping, Prüfung und Import
 * liegen in App\Services\TransactionImport.
 */
class TransactionCsvImportPage extends Page
{
    /** Maximale Dateigröße in KB */
    public const int MAX_FILE_SIZE = 10240;

    private const string DELIMITER_AUTO = 'auto';

    private const string PROFILE_MODE_UPDATE = 'update';

    private const string PROFILE_MODE_CREATE = 'create';

    protected string $view = 'filament.app.pages.transaction-csv-import-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::BANKS;

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'transactions/csv-import';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** Pfad der hochgeladenen Datei auf dem Import-Disk (relativ, unterhalb des Benutzerverzeichnisses) */
    #[Locked]
    public ?string $storedFile = null;

    #[Locked]
    public ?string $originalFileName = null;

    /** Erkennt, ob Datei, Format oder Profil seit der letzten Analyse geändert wurden */
    #[Locked]
    public ?string $analysisFingerprint = null;

    /** @var array<string, mixed>|null CsvAnalysis::toArray() */
    #[Locked]
    public ?array $analysis = null;

    /** @var array<string, mixed>|null ResolvedMapping::toArray() */
    #[Locked]
    public ?array $resolvedMapping = null;

    /** @var array<string, mixed>|null ImportReport::toArray() der Prüfung */
    #[Locked]
    public ?array $review = null;

    /** @var array<string, mixed>|null ImportReport::toArray() des durchgeführten Imports */
    #[Locked]
    public ?array $result = null;

    public static function getNavigationLabel(): string
    {
        return 'CSV-Import';
    }

    public function getTitle(): string
    {
        return 'Transaktionen aus CSV importieren';
    }

    public function mount(): void
    {
        $bankAccount = $this->findBankAccount(request()->integer('bankAccount') ?: null);
        $profile = CSVImportProfile::query()->find(request()->integer('profile') ?: $bankAccount?->csv_profile_id);

        $this->form->fill([
            'bank_account_id' => $bankAccount?->id,
            'profile_id' => $profile?->id,
            ...$this->formatState($profile),
            'mapping' => [],
            'save_profile' => false,
            'profile_mode' => $profile ? self::PROFILE_MODE_UPDATE : self::PROFILE_MODE_CREATE,
            'profile_name' => null,
            'assign_profile' => true,
            'skip_invalid_rows' => false,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    $this->fileStep(),
                    $this->previewStep(),
                    $this->mappingStep(),
                    $this->reviewStep(),
                ])
                    ->submitAction(
                        Action::make('import')
                            ->label('Import starten')
                            ->icon(Heroicon::OutlinedArrowDownTray)
                            ->color('success')
                            ->disabled(fn (): bool => ! $this->canImport())
                            ->action('import'),
                    ),
            ])
            ->statePath('data');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Wizard-Schritte
    // ─────────────────────────────────────────────────────────────────────────

    private function fileStep(): Step
    {
        return Step::make('Datei & Format')
            ->description('CSV-Datei, Konto und Profil wählen')
            ->icon(Heroicon::OutlinedDocumentArrowUp)
            ->schema([
                Grid::make(2)->schema([
                    Select::make('bank_account_id')
                        ->label('Bankkonto')
                        ->options(fn (): array => BankAccount::query()
                            ->where(BankAccount::user_id, auth()->id())
                            ->orderBy(BankAccount::name)
                            ->pluck(BankAccount::name, BankAccount::id)
                            ->all())
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                            $profileId = $this->findBankAccount((int) $state)?->csv_profile_id;

                            if ($profileId !== null && blank($get('profile_id'))) {
                                $set('profile_id', $profileId);
                                $this->applyProfile($profileId, $set);
                            }
                        }),

                    Select::make('profile_id')
                        ->label('Import-Profil')
                        ->placeholder('Ohne Profil (Zuordnung neu festlegen)')
                        ->options(fn (): array => CSVImportProfile::query()
                            ->orderBy(CSVImportProfile::name)
                            ->pluck(CSVImportProfile::name, CSVImportProfile::id)
                            ->all())
                        ->helperText('Übernimmt Format und Spaltenzuordnung eines gespeicherten Profils.')
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            $this->applyProfile($state === null ? null : (int) $state, $set);
                            $set('profile_mode', $state === null ? self::PROFILE_MODE_CREATE : self::PROFILE_MODE_UPDATE);
                        }),
                ]),

                FileUpload::make('file')
                    ->label('CSV-Datei')
                    ->helperText('CSV- oder TXT-Datei, maximal '.(self::MAX_FILE_SIZE / 1024).' MB.')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel', '.csv', '.txt'])
                    ->maxSize(self::MAX_FILE_SIZE)
                    ->storeFiles(false)
                    ->required(),

                Section::make('CSV-Format')
                    ->description('Wird aus dem Profil übernommen; nur bei Bedarf anpassen.')
                    ->collapsible()
                    ->collapsed(fn (Get $get): bool => filled($get('profile_id')))
                    ->columns(3)
                    ->schema([
                        Select::make(CSVImportProfile::delimiter)
                            ->label('Trennzeichen')
                            ->options([self::DELIMITER_AUTO => 'Automatisch erkennen', ...CsvFormat::delimiterOptions()])
                            ->required(),
                        Select::make(CSVImportProfile::encoding)
                            ->label('Zeichenkodierung')
                            ->options(CsvEncoding::options())
                            ->required(),
                        Select::make(CSVImportProfile::amount_format)
                            ->label('Betragsformat')
                            ->options(AmountFormat::options())
                            ->required(),
                        Toggle::make(CSVImportProfile::has_header)
                            ->label('Erste Zeile enthält Spaltennamen')
                            ->inline(false),
                        TextInput::make(CSVImportProfile::offset_header)
                            ->label('Zeilen vor der Kopfzeile überspringen')
                            ->helperText('Z. B. 13 bei ING-Exporten mit Kontoinformationen am Anfang.')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(255)
                            ->required(),
                        TextInput::make(CSVImportProfile::date_format)
                            ->label('Datumsformat')
                            ->placeholder('automatisch')
                            ->helperText('Leer = automatisch (31.12.2026, 2026-12-31, 31/12/2026 …), sonst PHP-Format wie d.m.Y.')
                            ->maxLength(32),
                        TextInput::make(CSVImportProfile::enclosure)
                            ->label('Textbegrenzer')
                            ->maxLength(1)
                            ->required(),
                        TextInput::make(CSVImportProfile::escape)
                            ->label('Escape-Zeichen')
                            ->helperText('Leer = kein Escape-Zeichen.')
                            ->maxLength(1),
                    ]),
            ])
            ->afterValidation(fn () => $this->analyzeFile());
    }

    private function previewStep(): Step
    {
        return Step::make('Vorschau')
            ->description('Erkannte Spalten und Beispielzeilen')
            ->icon(Heroicon::OutlinedTableCells)
            ->schema([
                View::make('filament.app.pages.transaction-csv-import.preview'),
            ]);
    }

    private function mappingStep(): Step
    {
        $selects = [];

        foreach (TransactionImportField::cases() as $field) {
            $selects[] = Select::make('mapping.'.$field->value)
                ->label($field->label())
                ->hint($field->value)
                ->placeholder('Nicht importieren')
                ->options(fn (): array => $this->getAnalysis()?->columnOptions() ?? [])
                ->required($field->isRequired())
                ->live()
                ->helperText(fn (Get $get): ?string => $this->sampleValueHint($get('mapping.'.$field->value)));
        }

        return Step::make('Zuordnung')
            ->description('CSV-Spalten den Transaktionsfeldern zuordnen')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->schema([
                View::make('filament.app.pages.transaction-csv-import.mapping-status'),

                Grid::make(3)->schema($selects),

                Section::make('Als Import-Profil speichern')
                    ->description('Format und Zuordnung für künftige Importe merken.')
                    ->compact()
                    ->schema([
                        Toggle::make('save_profile')
                            ->label('Zuordnung als Profil speichern')
                            ->live(),
                        Radio::make('profile_mode')
                            ->label('Profil')
                            ->options([
                                self::PROFILE_MODE_UPDATE => 'Ausgewähltes Profil aktualisieren',
                                self::PROFILE_MODE_CREATE => 'Neues Profil anlegen',
                            ])
                            ->visible(fn (Get $get): bool => $get('save_profile') && filled($get('profile_id')))
                            ->live(),
                        TextInput::make('profile_name')
                            ->label('Profilname')
                            ->placeholder('z. B. DKB Girokonto')
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => $this->isCreatingProfile($get))
                            ->required(fn (Get $get): bool => $this->isCreatingProfile($get)),
                        Toggle::make('assign_profile')
                            ->label('Profil diesem Bankkonto zuweisen')
                            ->visible(fn (Get $get): bool => (bool) $get('save_profile')),
                    ]),
            ])
            ->afterValidation(function (): void {
                $this->saveProfileIfRequested();
                $this->runReview();
            });
    }

    private function reviewStep(): Step
    {
        return Step::make('Prüfung')
            ->description('Ergebnis der Prüfung vor dem Import')
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->schema([
                View::make('filament.app.pages.transaction-csv-import.review'),
                Toggle::make('skip_invalid_rows')
                    ->label('Fehlerhafte Datensätze überspringen und nur gültige importieren')
                    ->visible(fn (): bool => ($this->getReview()?->invalid ?? 0) > 0)
                    ->live(),
            ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Aktionen
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Speichert die hochgeladene Datei, erkennt ggf. das Trennzeichen, analysiert die CSV
     * und löst das Mapping des Profils (bzw. einen Vorschlag) auf.
     *
     * @throws Halt
     */
    public function analyzeFile(): void
    {
        $upload = $this->getUploadedFile();

        if ($this->analysis !== null && $this->analysisFingerprint === $this->fingerprint($upload)) {
            return;
        }

        $this->storeUploadedFile($upload);
        $path = $this->getStoredFilePath();
        $analyzer = app(CsvAnalyzer::class);

        try {
            if (($this->data[CSVImportProfile::delimiter] ?? null) === self::DELIMITER_AUTO) {
                $this->data[CSVImportProfile::delimiter] = $analyzer->detectDelimiter($path, $this->buildFormat());
            }

            $analysis = $analyzer->analyze($path, $this->buildFormat());
        } catch (TransactionsImportException $exception) {
            $this->failStep('Die CSV-Datei konnte nicht analysiert werden.', $exception->getMessage());
        }

        if ($analysis->recordCount === 0) {
            $this->failStep('Keine Datensätze gefunden.', 'Bitte Trennzeichen und Anzahl der übersprungenen Zeilen prüfen.');
        }

        $resolved = app(MappingResolver::class)->resolve($this->getSelectedProfile(), $analysis);

        $this->analysis = $analysis->toArray();
        $this->resolvedMapping = $resolved->toArray();
        $this->data['mapping'] = $resolved->columns;
        $this->review = null;
        $this->analysisFingerprint = $this->fingerprint($upload);
    }

    public function runReview(): void
    {
        try {
            $report = app(TransactionImportService::class)->review($this->getStoredFilePath(), $this->buildConfiguration());
        } catch (TransactionsImportException $exception) {
            $this->failStep('Die CSV-Datei konnte nicht geprüft werden.', $exception->getMessage());
        }

        $this->review = $report->toArray();
    }

    public function import(): void
    {
        if ($this->analysis === null || $this->storedFile === null) {
            Notification::make()->title('Bitte zuerst eine CSV-Datei analysieren.')->danger()->send();

            return;
        }

        $configuration = $this->buildConfiguration();

        try {
            $report = app(TransactionImportService::class)->import($this->getStoredFilePath(), $configuration);
        } catch (TransactionsImportException $exception) {
            Notification::make()->title('Import fehlgeschlagen')->body($exception->getMessage())->danger()->send();

            return;
        }

        if (! $report->executed) {
            $this->review = $report->toArray();
            Notification::make()
                ->title('Import kann nicht gestartet werden.')
                ->body('Bitte die Fehler in der Prüfung beheben oder fehlerhafte Datensätze überspringen.')
                ->danger()
                ->send();

            return;
        }

        $this->result = $report->toArray();
        Storage::disk(AppConfig::FILESYSTEM_TRANSACTION_IMPORT)->delete($this->storedFile);

        Notification::make()
            ->title('Import abgeschlossen')
            ->body("{$report->imported} Transaktionen importiert, {$report->duplicates()} Duplikate übersprungen.")
            ->success()
            ->send();
    }

    public function startNewImport(): void
    {
        $this->redirect(self::getUrl(['bankAccount' => $this->data['bank_account_id'] ?? null]));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Daten für die Views
    // ─────────────────────────────────────────────────────────────────────────

    public function getAnalysis(): ?CsvAnalysis
    {
        return $this->analysis === null ? null : CsvAnalysis::fromArray($this->analysis);
    }

    public function getReview(): ?ImportReport
    {
        return $this->review === null ? null : ImportReport::fromArray($this->review);
    }

    public function getResult(): ?ImportReport
    {
        return $this->result === null ? null : ImportReport::fromArray($this->result);
    }

    public function getSelectedProfile(): ?CSVImportProfile
    {
        $profileId = $this->data['profile_id'] ?? null;

        return blank($profileId) ? null : CSVImportProfile::query()->find((int) $profileId);
    }

    public function getSelectedBankAccount(): ?BankAccount
    {
        return $this->findBankAccount((int) ($this->data['bank_account_id'] ?? 0));
    }

    public function getBankAccountUrl(): ?string
    {
        $bankAccount = $this->getSelectedBankAccount();

        return $bankAccount ? BankAccountResource::getUrl('view', ['record' => $bankAccount]) : null;
    }

    /** @return array<string, string> Feldbezeichnung => CSV-Spalte */
    public function getMappedColumns(): array
    {
        $header = $this->getAnalysis()?->columnOptions() ?? [];
        $mapped = [];

        foreach (TransactionImportField::cases() as $field) {
            $index = $this->currentMapping()[$field->value];

            if ($index !== null) {
                $mapped[$field->label()] = $header[$index] ?? '–';
            }
        }

        return $mapped;
    }

    /** @return list<string> CSV-Spalten, die keinem Feld zugeordnet sind */
    public function getIgnoredColumns(): array
    {
        $used = array_filter($this->currentMapping(), fn (?int $index) => $index !== null);
        $ignored = [];

        foreach ($this->getAnalysis()?->columnOptions() ?? [] as $index => $label) {
            if (! in_array($index, $used, true)) {
                $ignored[] = $label;
            }
        }

        return $ignored;
    }

    /** @return array<string, string> Feldbezeichnung => Hinweis */
    public function getMappingWarnings(): array
    {
        $warnings = [];

        foreach ($this->resolvedMapping['missing_columns'] ?? [] as $field => $column) {
            $label = TransactionImportField::tryFrom($field)?->label() ?? $field;
            $warnings[$label] = "Erwartete Spalte \"{$column}\" fehlt in dieser CSV-Datei.";
        }

        foreach ($this->resolvedMapping['ambiguous_columns'] ?? [] as $field => $column) {
            $label = TransactionImportField::tryFrom($field)?->label() ?? $field;
            $warnings[$label] = "Spalte \"{$column}\" kommt mehrfach vor und ist nicht eindeutig.";
        }

        return $warnings;
    }

    /** @return list<string> */
    public function getNewColumns(): array
    {
        return $this->resolvedMapping['new_columns'] ?? [];
    }

    public function canImport(): bool
    {
        return $this->getReview()?->canImport((bool) ($this->data['skip_invalid_rows'] ?? false)) ?? false;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Interne Helfer
    // ─────────────────────────────────────────────────────────────────────────

    private function buildFormat(): CsvFormat
    {
        return CsvFormat::fromArray($this->data);
    }

    private function buildConfiguration(): ImportConfiguration
    {
        $bankAccount = $this->getSelectedBankAccount();
        abort_unless($bankAccount instanceof BankAccount, 403);

        return new ImportConfiguration(
            format: $this->buildFormat(),
            columns: $this->currentMapping(),
            bankAccount: $bankAccount,
            userId: (int) auth()->id(),
            skipInvalidRows: (bool) ($this->data['skip_invalid_rows'] ?? false),
        );
    }

    /** @return array<string, int|null> */
    private function currentMapping(): array
    {
        return app(MappingResolver::class)->sanitize(
            (array) ($this->data['mapping'] ?? []),
            $this->getAnalysis()?->columnCount ?? 0,
        );
    }

    private function findBankAccount(?int $id): ?BankAccount
    {
        if (! $id) {
            return null;
        }

        return BankAccount::query()
            ->where(BankAccount::user_id, auth()->id())
            ->find($id);
    }

    /** @return array<string, mixed> Formularwerte des CSV-Formats */
    private function formatState(?CSVImportProfile $profile): array
    {
        $format = $profile ? CsvFormat::fromProfile($profile) : new CsvFormat(delimiter: self::DELIMITER_AUTO);

        return $format->toArray();
    }

    private function applyProfile(?int $profileId, Set $set): void
    {
        $profile = $profileId ? CSVImportProfile::query()->find($profileId) : null;

        foreach ($this->formatState($profile) as $key => $value) {
            $set($key, $value);
        }
    }

    private function isCreatingProfile(Get $get): bool
    {
        return $get('save_profile') && ($get('profile_mode') === self::PROFILE_MODE_CREATE || blank($get('profile_id')));
    }

    private function sampleValueHint(mixed $index): ?string
    {
        $analysis = $this->getAnalysis();

        if ($analysis === null || ! is_numeric($index)) {
            return null;
        }

        foreach ($analysis->previewRecords as $record) {
            $value = trim((string) $record->value((int) $index));

            if ($value !== '') {
                return 'Beispiel: '.Str::limit($value, 60);
            }
        }

        return 'Beispiel: (leer)';
    }

    private function saveProfileIfRequested(): void
    {
        $analysis = $this->getAnalysis();

        if (! ($this->data['save_profile'] ?? false) || $analysis === null) {
            return;
        }

        $profile = $this->isCreatingProfileState() ? new CSVImportProfile : $this->getSelectedProfile();

        if ($profile === null) {
            return;
        }

        if (! $profile->exists) {
            $profile->{CSVImportProfile::name} = trim((string) $this->data['profile_name']);
        }

        $profile->fill(app(MappingResolver::class)->toProfileAttributes($this->buildFormat(), $this->currentMapping(), $analysis));
        $profile->save();

        $bankAccount = $this->getSelectedBankAccount();
        if (($this->data['assign_profile'] ?? false) && $bankAccount !== null) {
            $bankAccount->update([BankAccount::csv_profile_id => $profile->id]);
        }

        // weitere Speichervorgänge aktualisieren das (neue) Profil statt Duplikate anzulegen
        $this->data['profile_id'] = $profile->id;
        $this->data['profile_mode'] = self::PROFILE_MODE_UPDATE;
        $this->data['save_profile'] = false;

        Notification::make()->title("Import-Profil \"{$profile->name}\" gespeichert.")->success()->send();
    }

    private function isCreatingProfileState(): bool
    {
        return ($this->data['profile_mode'] ?? null) === self::PROFILE_MODE_CREATE || blank($this->data['profile_id'] ?? null);
    }

    private function fingerprint(?TemporaryUploadedFile $upload): string
    {
        return md5(serialize([
            $upload?->getFilename() ?? $this->storedFile,
            $this->data['profile_id'] ?? null,
            array_intersect_key($this->data, $this->formatState(null)),
        ]));
    }

    private function getUploadedFile(): ?TemporaryUploadedFile
    {
        foreach ((array) ($this->data['file'] ?? []) as $file) {
            if ($file instanceof TemporaryUploadedFile) {
                return $file;
            }
        }

        return null;
    }

    /**
     * @throws Halt
     */
    private function storeUploadedFile(?TemporaryUploadedFile $upload): void
    {
        if ($upload === null) {
            if ($this->storedFile !== null) {
                return;
            }

            $this->failStep('Bitte eine CSV-Datei hochladen.');
        }

        if (! in_array(strtolower($upload->getClientOriginalExtension()), ['csv', 'txt'], true)
            || $upload->getSize() > self::MAX_FILE_SIZE * 1024) {
            $this->failStep('Ungültige Datei.', 'Erlaubt sind CSV- oder TXT-Dateien bis '.(self::MAX_FILE_SIZE / 1024).' MB.');
        }

        $disk = Storage::disk(AppConfig::FILESYSTEM_TRANSACTION_IMPORT);

        if ($this->storedFile !== null) {
            $disk->delete($this->storedFile);
        }

        // eigener, nicht erratbarer Dateiname; der Originalname wird nur angezeigt
        $this->storedFile = $upload->storeAs((string) auth()->id(), Str::uuid().'.csv', AppConfig::FILESYSTEM_TRANSACTION_IMPORT);
        $this->originalFileName = $upload->getClientOriginalName();
    }

    private function getStoredFilePath(): string
    {
        abort_if($this->storedFile === null, 404);

        return Storage::disk(AppConfig::FILESYSTEM_TRANSACTION_IMPORT)->path($this->storedFile);
    }

    /**
     * @throws Halt
     */
    private function failStep(string $title, ?string $body = null): never
    {
        Notification::make()->title($title)->body($body)->danger()->send();

        throw new Halt;
    }
}
