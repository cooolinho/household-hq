<?php

namespace Database\Seeders\Financial;

use App\Models\Financial\FixedCost;
use App\Models\Financial\Transaction;
use App\Models\Financial\TransactionCategory;
use App\Services\FixedCostNextBookingDateCalculator;
use Carbon\CarbonImmutable;
use Database\Seeders\Core\UserSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * TransactionSeeder – Demo-Transaktionen für Kategorien und den
 * TransactionFixedCostMatchingService.
 *
 * Szenarien:
 *   linked            (1–5)  → Score ≥ 70, eindeutiger Top-Kandidat → auto-verknüpft
 *   suggestion_created (6)   → Score ≥ 70, Gleichstand zweier FixedCosts → manuelle Entscheidung
 *   skipped            (7–10) → Score < 70 → keine Aktion
 *   skipped            (11)   → already linked → übersprungen
 *
 * Score-Formel:  Betrag (max 60) + Text (max 30) + Datum (max 10) = 0–100
 * Threshold:     70 (FIXED_COST_MATCHING_THRESHOLD)
 */
class TransactionSeeder extends Seeder
{
    public const int UNCATEGORIZED_TRANSACTION_COUNT = 10;

    public const string UNCATEGORIZED_TRANSACTION_PURPOSE_PREFIX = 'Nicht kategorisierte Demo-Buchung ';

    public const string FIXED_COST_TRANSACTION_DESCRIPTION = 'Demo-Fixkosten';

    public static function description(): string
    {
        return 'Legt Demo-Transaktionen für Kategorien und Fixkosten-Matching an';
    }

    /**
     * @return list<class-string<Seeder>>
     */
    public static function dependencies(): array
    {
        return [
            UserSeeder::class,
            BankAccountSeeder::class,
            FixedCostSeeder::class,
            TransactionCategorySeeder::class,
        ];
    }

    public function run(): void
    {
        $user = UserSeeder::getAdminUser();
        $bankAccount = BankAccountSeeder::getMainAccount();

        if (!$user || !$bankAccount) {
            $this->command?->warn('TransactionSeeder: User oder BankAccount fehlt – UserSeeder/BankAccountSeeder zuerst ausführen.');

            return;
        }

        $userId = $user->getKey();
        $bankAccountId = $bankAccount->getKey();
        $startDate = Carbon::now()->startOfDay();
        $this->removeLegacyBankAccountTransactions($userId, $bankAccountId);
        $categories = $this->getGlobalSubcategories();

        if ($categories->isEmpty()) {
            throw new RuntimeException('TransactionSeeder: Keine globalen Unterkategorien vorhanden.');
        }

        $this->createCategoryTransactions($userId, $bankAccountId, $categories, $startDate);
        $this->createFixedCostTransactions($userId, $bankAccountId, $startDate->toImmutable());

        // FixedCosts per Name abrufen (für "already linked"-Szenario)
        $fcSpotify = FixedCost::query()
            ->where(FixedCost::user_id, $userId)
            ->where(FixedCost::name, 'Spotify')
            ->first();

        // =====================================================================
        // GRUPPE A – LINKED (Score ≥ 70, eindeutig)
        // =====================================================================

        /**
         * #1 – Spotify
         * Betrag:  -9.99 vs -9.99   → 60 Pkt (exakt, dev=0)
         * Text:    "spotify" ⊆ "Spotify AB"    → 30 Pkt (str_contains payer)
         * Datum:   Startdatum, next_booking=Startdatum → 0 Tage → 10 Pkt
         * Gesamt:  100 Pkt ✅ AUTO-LINK → Spotify
         */
        $this->createTransaction($userId, $bankAccountId, [
            ...$this->dateFields($startDate, 0),
            Transaction::payer => 'Spotify AB',
            Transaction::description => 'Lastschrift SEPA',
            Transaction::purpose => 'Spotify Premium Abo',
            Transaction::amount => -9.99,
            Transaction::balance => -9.99,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ], $this->getCategory($categories, 'Streaming'));

        /**
         * #2 – Netflix
         * Betrag:  -15.99 vs -15.99  → 60 Pkt
         * Text:    "netflix" ⊆ "Netflix International BV" → 30 Pkt
         * Datum:   Startdatum, next_booking=aktueller Folgemonat → kurzer Abstand → bis zu 10 Pkt
         * Gesamt:  ca. 91+ Pkt (abhängig vom aktuellen Monatsabstand) ✅ AUTO-LINK → Netflix
         */
        $this->createTransaction($userId, $bankAccountId, [
            ...$this->dateFields($startDate, 0),
            Transaction::payer => 'Netflix International BV',
            Transaction::description => 'Lastschrift SEPA',
            Transaction::purpose => 'Netflix Monatsabo August',
            Transaction::amount => -15.99,
            Transaction::balance => -25.98,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ], $this->getCategory($categories, 'Streaming'));

        /**
         * #3 – Miete
         * Betrag:  -1000.00 vs -1000.00 → 60 Pkt
         * Text:    "miete" ⊆ "Miete August 2026" (purpose) → 30 Pkt
         * Datum:   Startdatum, next_booking=aktueller Folgemonat → kurzer Abstand → bis zu 10 Pkt
         * Gesamt:  ca. 91+ Pkt (abhängig vom aktuellen Monatsabstand) ✅ AUTO-LINK → Miete
         */
        $this->createTransaction($userId, $bankAccountId, [
            ...$this->dateFields($startDate, 0),
            Transaction::payer => 'Hausverwaltung Muster GmbH',
            Transaction::description => 'Dauerauftrag',
            Transaction::purpose => 'Miete August 2026',
            Transaction::amount => -1000.00,
            Transaction::balance => -1025.98,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ], $this->getCategory($categories, 'Wohnen & Wohnnebenkosten'));

        /**
         * #4 – Gehalt
         * Betrag:  2000.00 vs 2000.00 → 60 Pkt
         * Text:    "gehalt" ⊆ "Gehalt August 2026" (purpose) → 30 Pkt
         * Datum:   Startdatum - 1 Tag, next_booking=Ende des aktuellen Monats → kurzer Abstand
         * Gesamt:  >70 Pkt ✅ AUTO-LINK → Gehalt
         */
        $this->createTransaction($userId, $bankAccountId, [
            ...$this->dateFields($startDate, 1),
            Transaction::payer => 'Musterfirma GmbH',
            Transaction::description => 'Lohnzahlung',
            Transaction::purpose => 'Gehalt August 2026',
            Transaction::amount => 2000.00,
            Transaction::balance => 974.02,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ], $this->getCategory($categories, 'Lohn & Gehalt'));

        /**
         * #5 – Strom (Betrag knapp daneben)
         * Betrag:  -79.50 vs -80.00 → dev=0.625% → 45 Pkt (≤ 5%)
         * Text:    "strom" ⊆ "Strom Abschlag" (purpose) → 30 Pkt
         * Datum:   Startdatum - 1 Tag, next_booking=Startdatum → 1 Tag → 10 Pkt
         * Gesamt:  85 Pkt ✅ AUTO-LINK → Strom
         */
        $this->createTransaction($userId, $bankAccountId, [
            ...$this->dateFields($startDate, 1),
            Transaction::payer => 'Stadtwerke München GmbH',
            Transaction::description => 'Abbuchung Energie',
            Transaction::purpose => 'Strom Abschlag August',
            Transaction::amount => -79.50,
            Transaction::balance => 894.52,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ], $this->getCategory($categories, 'Wohnen & Wohnnebenkosten'));

        // =====================================================================
        // GRUPPE B – SUGGESTION (Gleichstand, Score ≥ 70)
        // =====================================================================

        /**
         * #6 – Amazon Prime (Gleichstand)
         * FixedCost "Amazon Prime A":
         *   Betrag: -8.99 vs -8.99 → 60 Pkt
         *   Text:   "amazon prime" ⊆ "Amazon Prime Mitgliedschaft" → 30 Pkt
         *   Datum:  Startdatum, next_booking=Startdatum → 0 Tage → 10 Pkt
         *   Score:  100 Pkt
         * FixedCost "Amazon Prime B": identisch (selber Name, Betrag, Datum) → ebenfalls 100 Pkt
         * Gleichstand → ✅ SUGGESTION für beide
         */
        $this->createTransaction($userId, $bankAccountId, [
            ...$this->dateFields($startDate, 0),
            Transaction::payer => 'Amazon Payments Europe',
            Transaction::description => 'Lastschrift SEPA',
            Transaction::purpose => 'Amazon Prime Mitgliedschaft',
            Transaction::amount => -8.99,
            Transaction::balance => 885.53,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ], $this->getCategory($categories, 'Streaming'));

        // =====================================================================
        // GRUPPE C – SKIPPED (Score < 70)
        // =====================================================================

        /**
         * #7 – Apotheke (kein passender FixedCost)
         * Betrag: -32.50 → passt zu keiner FixedCost (alle Abweichungen > 20%)
         * Text:   "apotheke müller" → passt nicht zu einem FixedCost-Namen
         * Gesamt: ~0 Pkt ✅ SKIPPED
         */
        $this->createTransaction($userId, $bankAccountId, [
            ...$this->dateFields($startDate, 14),
            Transaction::payer => 'Apotheke Müller',
            Transaction::description => 'Kartenzahlung',
            Transaction::purpose => 'Medikamente Rezept',
            Transaction::amount => -32.50,
            Transaction::balance => 853.03,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ], $this->getCategory($categories, 'Gesundheit'));

        /**
         * #8 – Supermarkt (Score zu niedrig)
         * vs Strom (-80.00): dev=(87.30-80)/80=9.1% → 25 Pkt; Text "strom" ∉ "REWE" → ~2 Pkt; Datum weit → 1 Pkt
         * Gesamt: ~28 Pkt ✅ SKIPPED
         */
        $this->createTransaction($userId, $bankAccountId, [
            ...$this->dateFields($startDate, 20),
            Transaction::payer => 'REWE Markt GmbH',
            Transaction::description => 'Kartenzahlung',
            Transaction::purpose => 'Lebensmittel Einkauf',
            Transaction::amount => -87.30,
            Transaction::balance => 765.73,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ], $this->getCategory($categories, 'Lebensmittel & Getränke'));

        /**
         * #9 – Spotify falscher Betrag (Score < 70 trotz gutem Text)
         * vs Spotify (-9.99): dev=(19.99-9.99)/9.99=100% → > 20% → 0 Pkt
         * Text: "spotify" ⊆ "Spotify AB" → 30 Pkt
         * Datum: Startdatum - 28 Tage, next_booking=Startdatum → 28 Tage → 1 Pkt
         * Gesamt: 31 Pkt ✅ SKIPPED
         */
        $this->createTransaction($userId, $bankAccountId, [
            ...$this->dateFields($startDate, 28),
            Transaction::payer => 'Spotify AB',
            Transaction::description => 'Lastschrift SEPA',
            Transaction::purpose => 'Spotify Duo Familienabo',
            Transaction::amount => -19.99,
            Transaction::balance => 745.74,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ], $this->getCategory($categories, 'Streaming'));

        /**
         * #10 – Strom-Gutschrift (falsches Vorzeichen → Amount-Score = 0)
         * vs Strom (-80.00): Vorzeichen +80 vs -80 → 0 Pkt Betrag
         * Text:  "strom" ⊆ "Strom Gutschrift" → 30 Pkt
         * Datum: Startdatum, next_booking=Startdatum → 0 Tage → 10 Pkt
         * Gesamt: 40 Pkt ✅ SKIPPED (trotz perfektem Datum + Text – Vorzeichen rettet korrekt)
         */
        $this->createTransaction($userId, $bankAccountId, [
            ...$this->dateFields($startDate, 0),
            Transaction::payer => 'Stadtwerke München GmbH',
            Transaction::description => 'Gutschrift',
            Transaction::purpose => 'Strom Gutschrift Überzahlung',
            Transaction::amount => 80.00,
            Transaction::balance => 825.74,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ], $this->getCategory($categories, 'Wohnen & Wohnnebenkosten'));

        // =====================================================================
        // GRUPPE D – SKIPPED (bereits verknüpft)
        // =====================================================================

        /**
         * #11 – Spotify bereits verknüpft
         * fixed_cost_id ist gesetzt → matchTransaction() gibt sofort 'skipped' zurück
         */
        if ($fcSpotify) {
            $this->createTransaction($userId, $bankAccountId, [
                ...$this->dateFields($startDate, 30),
                Transaction::payer => 'Spotify AB',
                Transaction::description => 'Lastschrift SEPA',
                Transaction::purpose => 'Spotify Premium Abo Juni',
                Transaction::amount => -9.99,
                Transaction::balance => 815.75,
                Transaction::balance_currency => 'EUR',
                Transaction::amount_currency => 'EUR',
                Transaction::fixed_cost_id => $fcSpotify->id,
            ], $this->getCategory($categories, 'Streaming'));
        }

        $this->createUncategorizedTransactions($userId, $bankAccountId, $startDate);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createFixedCostTransactions(
        int             $userId,
        int             $bankAccountId,
        CarbonImmutable $startDate,
    ): void
    {
        $calculator = app(FixedCostNextBookingDateCalculator::class);
        $fixedCosts = FixedCost::query()
            ->where(FixedCost::user_id, $userId)
            ->orderBy(FixedCost::id)
            ->get();

        /** @var array<int, list<CarbonImmutable>> $occurrences */
        $occurrences = [];
        /** @var array<int, array<string, bool>> $expectedPurposes */
        $expectedPurposes = [];

        foreach ($fixedCosts as $fixedCost) {
            $dates = $this->getFixedCostOccurrenceDates($fixedCost, $startDate, $calculator);
            $occurrences[(int)$fixedCost->getKey()] = $dates;

            foreach ($dates as $date) {
                $purpose = $this->fixedCostTransactionPurpose($fixedCost, $date);
                $expectedPurposes[(int)$fixedCost->getKey()][$purpose] = true;
            }
        }

        $this->removeStaleFixedCostTransactions($userId, $bankAccountId, $expectedPurposes);

        foreach ($fixedCosts as $fixedCost) {
            $balance = 1000.00;
            $fixedCostOccurrences = $occurrences[(int)$fixedCost->getKey()] ?? [];

            foreach ($fixedCostOccurrences as $date) {
                $amount = (float)$fixedCost->amount;
                $balance += $amount;

                $this->createTransaction($userId, $bankAccountId, [
                    Transaction::date => $date->toDateString(),
                    Transaction::value_date => $date->toDateString(),
                    Transaction::payer => sprintf('Demo-Fixkosten %s', $fixedCost->name),
                    Transaction::description => self::FIXED_COST_TRANSACTION_DESCRIPTION,
                    Transaction::purpose => $this->fixedCostTransactionPurpose($fixedCost, $date),
                    Transaction::amount => $amount,
                    Transaction::balance => round($balance, 2),
                    Transaction::balance_currency => 'EUR',
                    Transaction::amount_currency => 'EUR',
                    Transaction::fixed_cost_id => $fixedCost->getKey(),
                ]);
            }
        }
    }

    /**
     * @return list<CarbonImmutable>
     */
    private function getFixedCostOccurrenceDates(
        FixedCost                          $fixedCost,
        CarbonImmutable                    $startDate,
        FixedCostNextBookingDateCalculator $calculator,
    ): array
    {
        $lowerBound = $startDate->subYear()->startOfDay();
        $candidate = $fixedCost->next_booking_date?->toImmutable()->startOfDay() ?? $startDate;
        $steps = 0;

        while ($candidate->greaterThan($startDate)) {
            $candidate = $calculator->subtractInterval($fixedCost, $candidate);
            $steps++;
            $this->assertOccurrenceStepLimit($fixedCost, $steps);
        }

        $dates = [];
        while (
            $candidate->greaterThanOrEqualTo($lowerBound)
            && $candidate->lessThanOrEqualTo($startDate)
        ) {
            if ($calculator->isBookingDateAllowed($fixedCost, $candidate)) {
                $dates[] = $candidate;
            }

            $candidate = $calculator->subtractInterval($fixedCost, $candidate);
            $steps++;
            $this->assertOccurrenceStepLimit($fixedCost, $steps);
        }

        return array_reverse($dates);
    }

    private function assertOccurrenceStepLimit(FixedCost $fixedCost, int $steps): void
    {
        if ($steps > 400) {
            throw new RuntimeException(sprintf(
                'TransactionSeeder: Zu viele Intervallschritte für Fixkosten ID %d.',
                $fixedCost->getKey(),
            ));
        }
    }

    private function fixedCostTransactionPurpose(FixedCost $fixedCost, CarbonImmutable $date): string
    {
        return sprintf(
            '%s %s #%d %s',
            self::FIXED_COST_TRANSACTION_DESCRIPTION,
            $fixedCost->name,
            $fixedCost->getKey(),
            $date->toDateString(),
        );
    }

    /**
     * @param array<int, array<string, bool>> $expectedPurposes
     */
    private function removeStaleFixedCostTransactions(
        int   $userId,
        int   $bankAccountId,
        array $expectedPurposes,
    ): void
    {
        Transaction::query()
            ->where(Transaction::user_id, $userId)
            ->where(Transaction::bank_account_id, $bankAccountId)
            ->where(Transaction::description, self::FIXED_COST_TRANSACTION_DESCRIPTION)
            ->whereNotNull(Transaction::fixed_cost_id)
            ->get()
            ->each(function (Transaction $transaction) use ($expectedPurposes): void {
                $fixedCostId = (int)$transaction->{Transaction::fixed_cost_id};
                $purpose = (string)$transaction->{Transaction::purpose};

                if (!isset($expectedPurposes[$fixedCostId][$purpose])) {
                    $transaction->delete();
                }
            });
    }

    private function removeLegacyBankAccountTransactions(int $userId, int $bankAccountId): void
    {
        Transaction::query()
            ->where(Transaction::user_id, $userId)
            ->where(Transaction::bank_account_id, $bankAccountId)
            ->whereNull(Transaction::fixed_cost_id)
            ->where(Transaction::payer, 'like', 'Demo Händler %')
            ->where(Transaction::description, 'Demo-Kontobewegung')
            ->where(Transaction::purpose, 'like', 'Demo-Buchung %')
            ->delete();
    }

    /**
     * @return Collection<int, TransactionCategory>
     */
    private function getGlobalSubcategories(): Collection
    {
        return TransactionCategory::query()
            ->whereNull(TransactionCategory::user_id)
            ->whereNotNull(TransactionCategory::parent_id)
            ->orderBy(TransactionCategory::id)
            ->get();
    }

    /**
     * @param Collection<int, TransactionCategory> $categories
     */
    private function getCategory(Collection $categories, string $name): TransactionCategory
    {
        $category = $categories->firstWhere(TransactionCategory::name, $name);

        if (!$category instanceof TransactionCategory) {
            throw new RuntimeException(sprintf(
                'TransactionSeeder: Unterkategorie "%s" fehlt.',
                $name,
            ));
        }

        return $category;
    }

    /**
     * @return array<string, string>
     */
    private function dateFields(Carbon $startDate, int $daysAgo): array
    {
        $date = $startDate->copy()->subDays($daysAgo)->toDateString();

        return [
            Transaction::date => $date,
            Transaction::value_date => $date,
        ];
    }

    /**
     * @param Collection<int, TransactionCategory> $categories
     */
    private function createCategoryTransactions(
        int        $userId,
        int        $bankAccountId,
        Collection $categories,
        Carbon     $startDate,
    ): void
    {
        $balance = 1500.00;

        foreach ($categories->values() as $index => $category) {
            $amount = round(-12.50 - ($index * 1.75), 2);
            $balance += $amount;

            $this->createTransaction($userId, $bankAccountId, [
                ...$this->dateFields($startDate, $index + 1),
                Transaction::payer => sprintf('Demo-Kategorie %02d', $index + 1),
                Transaction::description => 'Demo-Kategorie',
                Transaction::purpose => sprintf('Demo-Ausgabe %s', $category->name),
                Transaction::amount => $amount,
                Transaction::balance => $balance,
                Transaction::balance_currency => 'EUR',
                Transaction::amount_currency => 'EUR',
            ], $category);
        }
    }

    private function createUncategorizedTransactions(
        int    $userId,
        int    $bankAccountId,
        Carbon $startDate,
    ): void
    {
        $balance = 300.00;

        for ($index = 1; $index <= self::UNCATEGORIZED_TRANSACTION_COUNT; $index++) {
            $amount = round(-30.00 + ($index * 4.50), 2);
            $balance += $amount;

            $this->createTransaction($userId, $bankAccountId, [
                ...$this->dateFields($startDate, $index + 50),
                Transaction::payer => sprintf('Unbekannter Händler %02d', $index),
                Transaction::description => $index % 2 === 0 ? 'Kartenzahlung' : 'Überweisung',
                Transaction::purpose => sprintf(
                    '%s%02d',
                    self::UNCATEGORIZED_TRANSACTION_PURPOSE_PREFIX,
                    $index,
                ),
                Transaction::amount => $amount,
                Transaction::balance => $balance,
                Transaction::balance_currency => 'EUR',
                Transaction::amount_currency => 'EUR',
            ]);
        }
    }

    private function createTransaction(
        int                  $userId,
        int                  $bankAccountId,
        array                $data,
        ?TransactionCategory $category = null,
    ): Transaction
    {
        $base = array_merge($data, [
            Transaction::user_id => $userId,
            Transaction::bank_account_id => $bankAccountId,
        ]);

        // Hash aus den Transaktionsdaten berechnen
        $base[Transaction::hash] = Transaction::createHash($base);

        $transaction = Transaction::query()->updateOrCreate(
            [
                Transaction::user_id => $base[Transaction::user_id],
                Transaction::bank_account_id => $base[Transaction::bank_account_id],
                Transaction::payer => $base[Transaction::payer] ?? null,
                Transaction::description => $base[Transaction::description] ?? null,
                Transaction::purpose => $base[Transaction::purpose] ?? null,
            ],
            $base,
        );

        if ($category instanceof TransactionCategory) {
            $transaction->transactionCategories()->syncWithoutDetaching([$category->getKey()]);
        }

        return $transaction;
    }
}
