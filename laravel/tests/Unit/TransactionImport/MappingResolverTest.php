<?php

namespace Tests\Unit\TransactionImport;

use App\Models\Financial\CSVImportProfile;
use App\Models\Financial\Transaction;
use App\Services\TransactionImport\CsvAnalysis;
use App\Services\TransactionImport\CsvFormat;
use App\Services\TransactionImport\MappingResolver;
use App\Services\TransactionImport\ResolvedMapping;
use Tests\TestCase;

class MappingResolverTest extends TestCase
{
    private const array ING_HEADER = ['Buchung', 'Wertstellungsdatum', 'Auftraggeber/Empfänger', 'Buchungstext', 'Verwendungszweck', 'Referenz', 'Saldo', 'Währung', 'Betrag', 'Währung'];

    public function test_it_suggests_a_mapping_from_typical_column_names(): void
    {
        $mapping = (new MappingResolver)->suggest($this->analysis(self::ING_HEADER));

        self::assertSame(ResolvedMapping::SOURCE_SUGGESTION, $mapping->source);
        self::assertSame(0, $mapping->columns[Transaction::date]);
        self::assertSame(1, $mapping->columns[Transaction::value_date]);
        self::assertSame(2, $mapping->columns[Transaction::payer]);
        self::assertSame(4, $mapping->columns[Transaction::purpose]);
        self::assertSame(8, $mapping->columns[Transaction::amount]);
        self::assertSame(6, $mapping->columns[Transaction::balance]);
        self::assertNull($mapping->columns[Transaction::balance_currency]);
    }

    public function test_a_saved_profile_is_restored_by_column_name_even_if_columns_moved(): void
    {
        $resolver = new MappingResolver;
        $original = $this->analysis(['Buchungsdatum', 'Beschreibung', 'Betrag']);
        $profile = $this->profile($resolver->toProfileAttributes(new CsvFormat, [
            Transaction::date => 0,
            Transaction::description => 1,
            Transaction::amount => 2,
        ], $original));

        $mapping = $resolver->resolve($profile, $this->analysis(['Betrag', 'IBAN', 'Buchungsdatum', 'Beschreibung']));

        self::assertSame(ResolvedMapping::SOURCE_PROFILE_HEADER, $mapping->source);
        self::assertSame(2, $mapping->columns[Transaction::date]);
        self::assertSame(3, $mapping->columns[Transaction::description]);
        self::assertSame(0, $mapping->columns[Transaction::amount]);
        self::assertSame(['IBAN'], $mapping->newColumns);
        self::assertSame([], $mapping->missingColumns);
    }

    public function test_it_reports_missing_and_ambiguous_columns(): void
    {
        $profile = $this->profile([
            CSVImportProfile::header_mapping => [Transaction::date => 'Buchungsdatum', Transaction::amount => 'Betrag', Transaction::amount_currency => 'Währung'],
            CSVImportProfile::mapping => [Transaction::date => 0, Transaction::amount => 1, Transaction::amount_currency => 3],
            CSVImportProfile::header_columns => ['Buchungsdatum', 'Betrag', 'Währung'],
        ]);

        $mapping = (new MappingResolver)->resolve($profile, $this->analysis(['Datum', 'Betrag', 'Währung', 'Währung']));

        self::assertNull($mapping->columns[Transaction::date]);
        self::assertSame([Transaction::date => 'Buchungsdatum'], $mapping->missingColumns);
        self::assertSame(1, $mapping->columns[Transaction::amount]);
        self::assertSame(3, $mapping->columns[Transaction::amount_currency], 'stored index resolves the duplicate name');
        self::assertSame(['Datum'], $mapping->newColumns);
    }

    public function test_duplicate_column_names_without_matching_index_are_ambiguous(): void
    {
        $profile = $this->profile([
            CSVImportProfile::header_mapping => [Transaction::amount_currency => 'Währung'],
            CSVImportProfile::mapping => [Transaction::amount_currency => 5],
        ]);

        $mapping = (new MappingResolver)->resolve($profile, $this->analysis(['Betrag', 'Währung', 'Währung']));

        self::assertNull($mapping->columns[Transaction::amount_currency]);
        self::assertSame([Transaction::amount_currency => 'Währung'], $mapping->ambiguousColumns);
    }

    public function test_legacy_profiles_are_resolved_by_column_index(): void
    {
        $profile = $this->profile([
            CSVImportProfile::mapping => [Transaction::date => '0', Transaction::amount => 8, Transaction::payer => '', Transaction::purpose => 12],
        ]);

        $mapping = (new MappingResolver)->resolve($profile, $this->analysis(self::ING_HEADER));

        self::assertSame(ResolvedMapping::SOURCE_PROFILE_INDEX, $mapping->source);
        self::assertSame(0, $mapping->columns[Transaction::date]);
        self::assertSame(8, $mapping->columns[Transaction::amount]);
        self::assertNull($mapping->columns[Transaction::payer]);
        self::assertSame([Transaction::purpose => 'Spalte 13'], $mapping->missingColumns);
    }

    public function test_sanitize_drops_unknown_fields_and_out_of_range_columns(): void
    {
        $columns = (new MappingResolver)->sanitize([
            Transaction::date => '0',
            Transaction::amount => 9,
            Transaction::user_id => 1,
            Transaction::payer => 'abc',
        ], 3);

        self::assertSame(0, $columns[Transaction::date]);
        self::assertNull($columns[Transaction::amount]);
        self::assertNull($columns[Transaction::payer]);
        self::assertArrayNotHasKey(Transaction::user_id, $columns);
    }

    /** @param list<string> $header */
    private function analysis(array $header): CsvAnalysis
    {
        return new CsvAnalysis($header, true, count($header), 1, 0, [], 0, []);
    }

    /** @param array<string, mixed> $attributes */
    private function profile(array $attributes): CSVImportProfile
    {
        return (new CSVImportProfile)->forceFill($attributes);
    }
}
