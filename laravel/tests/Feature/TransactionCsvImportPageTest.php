<?php

namespace Tests\Feature;

use App\AppConfig;
use App\Filament\App\Pages\TransactionCsvImportPage;
use App\Models\Enums\BankAccountTypeEnum;
use App\Models\Financial\BankAccount;
use App\Models\Financial\CSVImportProfile;
use App\Models\Financial\Transaction;
use App\Models\User;
use Filament\Schemas\Components\Wizard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionCsvImportPageTest extends TestCase
{
    use RefreshDatabase;

    private const string CSV = "Buchungsdatum;Zahlungsempfänger;Verwendungszweck;Betrag;IBAN\n"
        ."31.12.2026;Stadtwerke;Abschlag;-42,50;DE00\n"
        ."30.12.2026;Arbeitgeber;Gehalt;1.234,56;DE01\n";

    private User $user;

    private BankAccount $bankAccount;

    public function test_the_wizard_imports_a_file_and_saves_the_mapping_as_profile(): void
    {
        Livewire::actingAs($this->user)
            ->test(TransactionCsvImportPage::class)
            ->fillForm([
                'bank_account_id' => $this->bankAccount->id,
                'profile_id' => null,
                CSVImportProfile::delimiter => 'auto',
                'file' => UploadedFile::fake()->createWithContent('dkb.csv', self::CSV),
            ])
            ->tap(fn (Testable $page) => $this->completeStep($page, 0))
            ->assertHasNoFormErrors()
            ->assertSet('analysis.record_count', 2)
            ->assertSet('data.'.CSVImportProfile::delimiter, ';')
            // Vorschlag anhand der Spaltennamen
            ->assertSet('data.mapping.'.Transaction::date, 0)
            ->assertSet('data.mapping.'.Transaction::amount, 3)
            ->tap(fn (Testable $page) => $this->completeStep($page, 1))
            ->fillForm([
                'mapping' => [Transaction::payer => 1, Transaction::purpose => 2],
                'save_profile' => true,
                'profile_name' => 'DKB Girokonto',
                'assign_profile' => true,
            ])
            ->tap(fn (Testable $page) => $this->completeStep($page, 2))
            ->assertHasNoFormErrors()
            ->assertSet('review.valid', 2)
            ->call('import')
            ->assertSet('result.imported', 2)
            ->assertSee('Import abgeschlossen');

        self::assertSame(2, Transaction::query()->where(Transaction::bank_account_id, $this->bankAccount->id)->count());

        $profile = CSVImportProfile::query()->where(CSVImportProfile::name, 'DKB Girokonto')->sole();
        self::assertSame('Buchungsdatum', $profile->header_mapping[Transaction::date]);
        self::assertSame('Betrag', $profile->header_mapping[Transaction::amount]);
        self::assertSame(3, $profile->mapping[Transaction::amount]);
        self::assertSame(';', $profile->delimiter);
        self::assertSame($profile->id, $this->bankAccount->refresh()->csv_profile_id);
    }

    public function test_a_selected_profile_prefills_format_and_mapping(): void
    {
        $profile = CSVImportProfile::query()->create([
            CSVImportProfile::name => 'DKB',
            CSVImportProfile::delimiter => ';',
            CSVImportProfile::amount_format => 'de_de',
            CSVImportProfile::header_mapping => [Transaction::date => 'Buchungsdatum', Transaction::amount => 'Betrag', Transaction::balance => 'Saldo'],
            CSVImportProfile::mapping => [Transaction::date => 0, Transaction::amount => 3, Transaction::balance => 4],
            CSVImportProfile::header_columns => ['Buchungsdatum', 'Zahlungsempfänger', 'Verwendungszweck', 'Betrag', 'Saldo'],
        ]);

        Livewire::withQueryParams(['profile' => $profile->id, 'bankAccount' => $this->bankAccount->id])
            ->actingAs($this->user)
            ->test(TransactionCsvImportPage::class)
            ->assertSet('data.profile_id', $profile->id)
            ->assertSet('data.'.CSVImportProfile::delimiter, ';')
            ->fillForm(['file' => UploadedFile::fake()->createWithContent('dkb.csv', self::CSV)])
            ->tap(fn (Testable $page) => $this->completeStep($page, 0))
            ->assertHasNoFormErrors()
            ->assertSet('data.mapping.'.Transaction::date, 0)
            ->assertSet('data.mapping.'.Transaction::amount, 3)
            ->assertSet('resolvedMapping.missing_columns', [Transaction::balance => 'Saldo'])
            ->assertSet('resolvedMapping.new_columns', ['IBAN'])
            ->tap(fn (Testable $page) => $this->completeStep($page, 1))
            ->assertSee('Erwartete Spalte "Saldo" fehlt in dieser CSV-Datei.')
            ->assertSee('Neue Spalten gegenüber dem Profil: IBAN');
    }

    public function test_bank_accounts_of_other_users_cannot_be_selected(): void
    {
        $foreignAccount = BankAccount::query()->create([
            BankAccount::user_id => User::factory()->create()->id,
            BankAccount::name => 'Fremdes Konto',
            BankAccount::type => BankAccountTypeEnum::GIRO->name,
            BankAccount::csv_profile_id => $this->bankAccount->csv_profile_id,
        ]);

        Livewire::actingAs($this->user)
            ->test(TransactionCsvImportPage::class)
            ->fillForm([
                'bank_account_id' => $foreignAccount->id,
                'file' => UploadedFile::fake()->createWithContent('dkb.csv', self::CSV),
            ])
            ->tap(fn (Testable $page) => $this->completeStep($page, 0))
            ->assertHasFormErrors(['bank_account_id']);

        self::assertNull(Livewire::actingAs($this->user)
            ->withQueryParams(['bankAccount' => $foreignAccount->id])
            ->test(TransactionCsvImportPage::class)
            ->get('data.bank_account_id'));
    }

    /**
     * Im Browser übergibt Alpine den aktuellen Schritt; der Filament-Testhelfer startet je Request wieder bei 0.
     */
    private function completeStep(Testable $page, int $stepIndex): void
    {
        $wizard = $page->instance()->form->getComponent(fn ($component) => $component instanceof Wizard);

        $page->call('callSchemaComponentMethod', $wizard->getKey(), 'nextStep', [$stepIndex]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
        Storage::fake(AppConfig::FILESYSTEM_TRANSACTION_IMPORT);

        $this->user = User::factory()->create();
        $this->bankAccount = BankAccount::query()->create([
            BankAccount::user_id => $this->user->id,
            BankAccount::name => 'Girokonto',
            BankAccount::type => BankAccountTypeEnum::GIRO->name,
            BankAccount::csv_profile_id => CSVImportProfile::query()->create([CSVImportProfile::name => 'Bestehendes Profil'])->id,
        ]);
    }
}
