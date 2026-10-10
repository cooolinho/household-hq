<?php

namespace Tests\Feature;

use App\Jobs\Scheduled\CategorizeTransactionsJob;
use App\Jobs\Scheduled\FixedCostTransactionMatchingJob;
use App\Models\Enums\BankAccountTypeEnum;
use App\Models\Financial\BankAccount;
use App\Models\Financial\CSVImportProfile;
use App\Models\Financial\Transaction;
use App\Models\User;
use App\Services\TransactionImport\AmountFormat;
use App\Services\TransactionImport\CsvAnalyzer;
use App\Services\TransactionImport\CsvFormat;
use App\Services\TransactionImport\ImportConfiguration;
use App\Services\TransactionImport\ImportProblem;
use App\Services\TransactionImport\MappingResolver;
use App\Services\TransactionImport\TransactionImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class TransactionCsvImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private const string DKB_CSV = "Buchungsdatum;Wertstellung;Zahlungsempfänger;Verwendungszweck;Betrag\n"
        ."31.12.2026;31.12.2026;Stadtwerke;Abschlag Dezember;-42,50\n"
        ."30.12.2026;30.12.2026;Arbeitgeber GmbH;Gehalt;1.234,56\n"
        ."29.12.2026;;Bäckerei;;-3,10\n";

    private User $user;

    private BankAccount $bankAccount;

    /** @var list<string> */
    private array $files = [];

    public function test_it_imports_a_semicolon_csv_with_german_formats(): void
    {
        $report = $this->service()->import($this->csv(self::DKB_CSV), $this->configuration([
            Transaction::date => 0,
            Transaction::value_date => 1,
            Transaction::payer => 2,
            Transaction::purpose => 3,
            Transaction::amount => 4,
        ]));

        self::assertTrue($report->executed);
        self::assertSame(3, $report->imported);
        self::assertSame(0, $report->invalid);

        $transaction = Transaction::query()->where(Transaction::payer, 'Arbeitgeber GmbH')->firstOrFail();
        self::assertSame('2026-12-30', $transaction->date->toDateString());
        self::assertSame(1234.56, $transaction->amount);
        self::assertSame($this->bankAccount->id, $transaction->bank_account_id);
        self::assertSame($this->user->id, $transaction->user_id);
        self::assertSame(-42.5, Transaction::query()->where(Transaction::payer, 'Stadtwerke')->firstOrFail()->amount);

        $bakery = Transaction::query()->where(Transaction::payer, 'Bäckerei')->firstOrFail();
        self::assertNull($bakery->value_date);
        self::assertNull($bakery->purpose);

        Bus::assertChained([
            CategorizeTransactionsJob::class,
            FixedCostTransactionMatchingJob::class,
        ]);
    }

    public function test_it_imports_a_comma_csv_with_english_formats(): void
    {
        $path = $this->csv("Date,Payee,Description,Amount,Currency\n2026-12-31,\"Shop, Inc.\",Card payment,\"-1,234.50\",EUR\n");
        $configuration = $this->configuration(
            [Transaction::date => 0, Transaction::payer => 1, Transaction::description => 2, Transaction::amount => 3, Transaction::amount_currency => 4],
            new CsvFormat(delimiter: ',', amountFormat: AmountFormat::English),
        );

        $report = $this->service()->import($path, $configuration);

        self::assertSame(1, $report->imported);
        $transaction = Transaction::query()->sole();
        self::assertSame('Shop, Inc.', $transaction->payer);
        self::assertSame(-1234.5, $transaction->amount);
        self::assertSame('EUR', $transaction->amount_currency);
    }

    public function test_reimporting_the_same_file_detects_duplicates(): void
    {
        $path = $this->csv(self::DKB_CSV);
        $configuration = $this->configuration([Transaction::date => 0, Transaction::payer => 2, Transaction::amount => 4]);

        $this->service()->import($path, $configuration);
        $report = $this->service()->review($path, $configuration);

        self::assertSame(0, $report->valid);
        self::assertSame(3, $report->duplicatesInDatabase);
        self::assertFalse($report->canImport(false));
        self::assertSame(3, Transaction::query()->count());
    }

    public function test_identical_rows_within_one_file_are_imported_once(): void
    {
        $path = $this->csv("Datum;Betrag\n01.01.2026;-5,00\n01.01.2026;-5,00\n");

        $report = $this->service()->import($path, $this->configuration([Transaction::date => 0, Transaction::amount => 1]));

        self::assertSame(1, $report->imported);
        self::assertSame(1, $report->duplicatesInFile);
        self::assertSame([3], $report->duplicateLines);
    }

    public function test_existing_transactions_from_the_previous_importer_are_recognised(): void
    {
        // Hash, wie ihn der bisherige Import (TransactionsCSVReaderService) gebildet hat
        Transaction::factory()->forUser($this->user->id)->forBankAccount($this->bankAccount->id)->create([
            Transaction::date => '2026-12-31',
            Transaction::amount => -42.5,
            Transaction::hash => Transaction::createHash([
                Transaction::date => '2026-12-31',
                Transaction::amount => -42.5,
                Transaction::value_date => '2026-12-31',
                Transaction::payer => 'Stadtwerke',
                Transaction::description => null,
                Transaction::purpose => 'Abschlag Dezember',
                Transaction::balance => null,
                Transaction::user_id => $this->user->id,
            ]),
        ]);

        $report = $this->service()->review($this->csv(self::DKB_CSV), $this->configuration([
            Transaction::date => 0,
            Transaction::value_date => 1,
            Transaction::payer => 2,
            Transaction::purpose => 3,
            Transaction::amount => 4,
        ]));

        self::assertSame(1, $report->duplicatesInDatabase);
        self::assertSame(2, $report->valid);
    }

    public function test_a_partially_invalid_file_is_blocked_and_reports_the_lines(): void
    {
        $path = $this->csv("Datum;Betrag\n01.01.2026;-5,00\n32.01.2026;1,00\n02.01.2026;abc\n;3,00\n04.01.2026\n");
        $configuration = $this->configuration([Transaction::date => 0, Transaction::amount => 1]);

        $report = $this->service()->import($path, $configuration);

        self::assertFalse($report->executed);
        self::assertSame(0, Transaction::query()->count());
        self::assertSame(1, $report->valid);
        self::assertSame(4, $report->invalid);
        self::assertSame([3], $report->problems[ImportProblem::InvalidDate->value]['lines']);
        self::assertSame([4], $report->problems[ImportProblem::InvalidAmount->value]['lines']);
        self::assertSame([5], $report->problems[ImportProblem::MissingRequired->value]['lines']);
        self::assertSame([6], $report->problems[ImportProblem::MissingColumn->value]['lines']);
        self::assertSame('3 Datensätze enthalten ungültige Beträge.', ImportProblem::InvalidAmount->summary(3));
        Bus::assertNothingDispatched();
    }

    public function test_invalid_rows_can_be_skipped(): void
    {
        $path = $this->csv("Datum;Betrag\n01.01.2026;-5,00\n02.01.2026;abc\n");

        $report = $this->service()->import($path, $this->configuration([Transaction::date => 0, Transaction::amount => 1], skipInvalidRows: true));

        self::assertTrue($report->executed);
        self::assertSame(1, $report->imported);
        self::assertSame(1, $report->invalid);
        self::assertSame(1, $report->skipped());
        self::assertSame(1, Transaction::query()->count());
    }

    public function test_unmapped_required_fields_block_the_import(): void
    {
        $report = $this->service()->import($this->csv(self::DKB_CSV), $this->configuration([Transaction::date => 0]));

        self::assertFalse($report->executed);
        self::assertSame(['Pflichtfeld "Betrag" ist keiner CSV-Spalte zugeordnet.'], $report->mappingErrors);
        self::assertSame(0, Transaction::query()->count());
    }

    public function test_a_saved_profile_restores_the_mapping_for_a_new_file(): void
    {
        $analyzer = app(CsvAnalyzer::class);
        $resolver = app(MappingResolver::class);
        $format = new CsvFormat;
        $firstFile = $this->csv(self::DKB_CSV);
        $profile = CSVImportProfile::query()->create([
            CSVImportProfile::name => 'DKB Girokonto',
            ...$resolver->toProfileAttributes($format, [
                Transaction::date => 0,
                Transaction::payer => 2,
                Transaction::amount => 4,
            ], $analyzer->analyze($firstFile, $format)),
        ]);

        // gleicher Export mit anderer Spaltenreihenfolge
        $secondFile = $this->csv("Betrag;Buchungsdatum;Zahlungsempfänger\n-9,99;15.01.2027;Streaming\n");
        $profile->refresh();
        $secondFormat = CsvFormat::fromProfile($profile);
        $mapping = $resolver->resolve($profile, $analyzer->analyze($secondFile, $secondFormat));

        $report = $this->service()->import($secondFile, $this->configuration($mapping->columns, $secondFormat));

        self::assertSame(1, $report->imported);
        $transaction = Transaction::query()->sole();
        self::assertSame('Streaming', $transaction->payer);
        self::assertSame(-9.99, $transaction->amount);
        self::assertSame('2027-01-15', $transaction->date->toDateString());
    }

    public function test_it_imports_an_ing_export_with_a_legacy_index_profile(): void
    {
        $profile = CSVImportProfile::query()->create([
            CSVImportProfile::name => 'ING',
            CSVImportProfile::delimiter => ';',
            CSVImportProfile::offset_header => 13,
            CSVImportProfile::mapping => [
                Transaction::date => 0,
                Transaction::value_date => 1,
                Transaction::payer => 2,
                Transaction::description => 3,
                Transaction::purpose => 4,
                Transaction::balance => 6,
                Transaction::balance_currency => 7,
                Transaction::amount => 8,
                Transaction::amount_currency => 9,
            ],
        ]);
        $path = $this->csv("Umsatzanzeige;Datei erstellt am: 08.10.2026 15:57\n\nIBAN;DE00\nKontoname;Girokonto\nBank;ING\nKunde;Test\n"
            ."Zeitraum;08.09.2026 - 08.10.2026\nSaldo;1.072,61;EUR\n\nSortierung;Datum absteigend\n\nIn der CSV-Datei finden Sie alle bereits gebuchten Umsätze.\n\n"
            ."Buchung;Wertstellungsdatum;Auftraggeber/Empfänger;Buchungstext;Verwendungszweck;Referenz;Saldo;Währung;Betrag;Währung\n"
            ."07.10.2026;07.10.2026;VISA ANTHROPIC;Lastschrift;KAUFUMSATZ;;1.072,61;EUR;-23,80;EUR\n"
            ."07.10.2026;07.10.2026;TSV Grosenkneten;Lastschrift;Beitragseinzug;20260725-C000000006;1.096,41;EUR;-30,00;EUR\n");
        $profile->refresh();
        $format = CsvFormat::fromProfile($profile);
        $mapping = app(MappingResolver::class)->resolve($profile, app(CsvAnalyzer::class)->analyze($path, $format));

        $report = $this->service()->import($path, $this->configuration($mapping->columns, $format));

        self::assertSame(2, $report->imported);
        $transaction = Transaction::query()->where(Transaction::payer, 'VISA ANTHROPIC')->sole();
        self::assertSame(-23.8, $transaction->amount);
        self::assertSame(1072.61, $transaction->balance);
        self::assertSame('EUR', $transaction->balance_currency);
        self::assertSame('2026-10-07', $transaction->date->toDateString());
        self::assertSame(1072.61, $this->bankAccount->refresh()->balance + 0.0);
    }

    public function test_one_csv_column_can_feed_several_fields(): void
    {
        $path = $this->csv("Buchungstag;Name Zahlungsbeteiligter;Betrag;Waehrung;Saldo nach Buchung\n02.10.2026;HOL AB GETRAENKEMARKT;-29,87;EUR;115,99\n");

        $report = $this->service()->import($path, $this->configuration([
            Transaction::date => 0,
            Transaction::payer => 1,
            Transaction::amount => 2,
            Transaction::amount_currency => 3,
            Transaction::balance_currency => 3,
            Transaction::balance => 4,
        ]));

        self::assertSame(1, $report->imported);
        $transaction = Transaction::query()->sole();
        self::assertSame('EUR', $transaction->amount_currency);
        self::assertSame('EUR', $transaction->balance_currency);
        self::assertSame(115.99, $transaction->balance);
    }

    /**
     * @param  array<string, int|null>  $columns
     */
    private function configuration(array $columns, ?CsvFormat $format = null, bool $skipInvalidRows = false): ImportConfiguration
    {
        return new ImportConfiguration(
            format: $format ?? new CsvFormat,
            columns: $columns,
            bankAccount: $this->bankAccount,
            userId: $this->user->id,
            skipInvalidRows: $skipInvalidRows,
        );
    }

    private function service(): TransactionImportService
    {
        return app(TransactionImportService::class);
    }

    private function csv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'transaction_import_');
        file_put_contents($path, $content);
        $this->files[] = $path;

        return $path;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();

        $this->user = User::factory()->create();
        $this->bankAccount = BankAccount::query()->create([
            BankAccount::user_id => $this->user->id,
            BankAccount::name => 'Girokonto',
            BankAccount::type => BankAccountTypeEnum::GIRO->name,
            BankAccount::csv_profile_id => CSVImportProfile::query()->create([CSVImportProfile::name => 'Girokonto-Profil'])->id,
        ]);
    }

    protected function tearDown(): void
    {
        array_map('unlink', $this->files);

        parent::tearDown();
    }
}
