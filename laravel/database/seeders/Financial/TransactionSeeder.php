<?php

namespace Database\Seeders\Financial;

use App\Models\Financial\FixedCost;
use App\Models\Financial\Transaction;
use Database\Seeders\Core\UserSeeder;
use Illuminate\Database\Seeder;

/**
 * TransactionSeeder – Testdaten für den TransactionFixedCostMatchingService.
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
    public static function description(): string
    {
        return 'Legt Demo-Transaktionen für das Fixkosten-Matching an';
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
         * Datum:   2026-07-29, next_booking=2026-07-29 → 0 Tage → 10 Pkt
         * Gesamt:  100 Pkt ✅ AUTO-LINK → Spotify
         */
        $this->createTransaction($userId, $bankAccountId, [
            Transaction::date => '2026-07-29',
            Transaction::value_date => '2026-07-29',
            Transaction::payer => 'Spotify AB',
            Transaction::description => 'Lastschrift SEPA',
            Transaction::purpose => 'Spotify Premium Abo',
            Transaction::amount => -9.99,
            Transaction::balance => -9.99,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ]);

        /**
         * #2 – Netflix
         * Betrag:  -15.99 vs -15.99  → 60 Pkt
         * Text:    "netflix" ⊆ "Netflix International BV" → 30 Pkt
         * Datum:   2026-08-01, next_booking=2026-08-01 → 0 Tage → 10 Pkt
         * Gesamt:  100 Pkt ✅ AUTO-LINK → Netflix
         */
        $this->createTransaction($userId, $bankAccountId, [
            Transaction::date => '2026-08-01',
            Transaction::value_date => '2026-08-01',
            Transaction::payer => 'Netflix International BV',
            Transaction::description => 'Lastschrift SEPA',
            Transaction::purpose => 'Netflix Monatsabo August',
            Transaction::amount => -15.99,
            Transaction::balance => -25.98,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ]);

        /**
         * #3 – Miete
         * Betrag:  -1000.00 vs -1000.00 → 60 Pkt
         * Text:    "miete" ⊆ "Miete August 2026" (purpose) → 30 Pkt
         * Datum:   2026-08-01, next_booking=2026-08-01 → 0 Tage → 10 Pkt
         * Gesamt:  100 Pkt ✅ AUTO-LINK → Miete
         */
        $this->createTransaction($userId, $bankAccountId, [
            Transaction::date => '2026-08-01',
            Transaction::value_date => '2026-08-01',
            Transaction::payer => 'Hausverwaltung Muster GmbH',
            Transaction::description => 'Dauerauftrag',
            Transaction::purpose => 'Miete August 2026',
            Transaction::amount => -1000.00,
            Transaction::balance => -1025.98,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ]);

        /**
         * #4 – Gehalt
         * Betrag:  2000.00 vs 2000.00 → 60 Pkt
         * Text:    "gehalt" ⊆ "Gehalt August 2026" (purpose) → 30 Pkt
         * Datum:   2026-07-31, next_booking=2026-07-31 → 0 Tage → 10 Pkt
         * Gesamt:  100 Pkt ✅ AUTO-LINK → Gehalt
         */
        $this->createTransaction($userId, $bankAccountId, [
            Transaction::date => '2026-07-31',
            Transaction::value_date => '2026-07-31',
            Transaction::payer => 'Musterfirma GmbH',
            Transaction::description => 'Lohnzahlung',
            Transaction::purpose => 'Gehalt August 2026',
            Transaction::amount => 2000.00,
            Transaction::balance => 974.02,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ]);

        /**
         * #5 – Strom (Betrag knapp daneben)
         * Betrag:  -79.50 vs -80.00 → dev=0.625% → 45 Pkt (≤ 5%)
         * Text:    "strom" ⊆ "Strom Abschlag" (purpose) → 30 Pkt
         * Datum:   2026-08-14, next_booking=2026-08-15 → 1 Tag → 10 Pkt
         * Gesamt:  85 Pkt ✅ AUTO-LINK → Strom
         */
        $this->createTransaction($userId, $bankAccountId, [
            Transaction::date => '2026-08-14',
            Transaction::value_date => '2026-08-14',
            Transaction::payer => 'Stadtwerke München GmbH',
            Transaction::description => 'Abbuchung Energie',
            Transaction::purpose => 'Strom Abschlag August',
            Transaction::amount => -79.50,
            Transaction::balance => 894.52,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ]);

        // =====================================================================
        // GRUPPE B – SUGGESTION (Gleichstand, Score ≥ 70)
        // =====================================================================

        /**
         * #6 – Amazon Prime (Gleichstand)
         * FixedCost "Amazon Prime A":
         *   Betrag: -8.99 vs -8.99 → 60 Pkt
         *   Text:   "amazon prime" ⊆ "Amazon Prime Mitgliedschaft" → 30 Pkt
         *   Datum:  2026-07-29, next_booking=2026-07-29 → 0 Tage → 10 Pkt
         *   Score:  100 Pkt
         * FixedCost "Amazon Prime B": identisch (selber Name, Betrag, Datum) → ebenfalls 100 Pkt
         * Gleichstand → ✅ SUGGESTION für beide
         */
        $this->createTransaction($userId, $bankAccountId, [
            Transaction::date => '2026-07-29',
            Transaction::value_date => '2026-07-29',
            Transaction::payer => 'Amazon Payments Europe',
            Transaction::description => 'Lastschrift SEPA',
            Transaction::purpose => 'Amazon Prime Mitgliedschaft',
            Transaction::amount => -8.99,
            Transaction::balance => 885.53,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ]);

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
            Transaction::date => '2026-07-15',
            Transaction::value_date => '2026-07-15',
            Transaction::payer => 'Apotheke Müller',
            Transaction::description => 'Kartenzahlung',
            Transaction::purpose => 'Medikamente Rezept',
            Transaction::amount => -32.50,
            Transaction::balance => 853.03,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ]);

        /**
         * #8 – Supermarkt (Score zu niedrig)
         * vs Strom (-80.00): dev=(87.30-80)/80=9.1% → 25 Pkt; Text "strom" ∉ "REWE" → ~2 Pkt; Datum weit → 1 Pkt
         * Gesamt: ~28 Pkt ✅ SKIPPED
         */
        $this->createTransaction($userId, $bankAccountId, [
            Transaction::date => '2026-07-20',
            Transaction::value_date => '2026-07-20',
            Transaction::payer => 'REWE Markt GmbH',
            Transaction::description => 'Kartenzahlung',
            Transaction::purpose => 'Lebensmittel Einkauf',
            Transaction::amount => -87.30,
            Transaction::balance => 765.73,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ]);

        /**
         * #9 – Spotify falscher Betrag (Score < 70 trotz gutem Text)
         * vs Spotify (-9.99): dev=(19.99-9.99)/9.99=100% → > 20% → 0 Pkt
         * Text: "spotify" ⊆ "Spotify AB" → 30 Pkt
         * Datum: 2026-07-01, next_booking=2026-07-29 → 28 Tage → 1 Pkt
         * Gesamt: 31 Pkt ✅ SKIPPED
         */
        $this->createTransaction($userId, $bankAccountId, [
            Transaction::date => '2026-07-01',
            Transaction::value_date => '2026-07-01',
            Transaction::payer => 'Spotify AB',
            Transaction::description => 'Lastschrift SEPA',
            Transaction::purpose => 'Spotify Duo Familienabo',
            Transaction::amount => -19.99,
            Transaction::balance => 745.74,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ]);

        /**
         * #10 – Strom-Gutschrift (falsches Vorzeichen → Amount-Score = 0)
         * vs Strom (-80.00): Vorzeichen +80 vs -80 → 0 Pkt Betrag
         * Text:  "strom" ⊆ "Strom Gutschrift" → 30 Pkt
         * Datum: 2026-08-15, next_booking=2026-08-15 → 0 Tage → 10 Pkt
         * Gesamt: 40 Pkt ✅ SKIPPED (trotz perfektem Datum + Text – Vorzeichen rettet korrekt)
         */
        $this->createTransaction($userId, $bankAccountId, [
            Transaction::date => '2026-08-15',
            Transaction::value_date => '2026-08-15',
            Transaction::payer => 'Stadtwerke München GmbH',
            Transaction::description => 'Gutschrift',
            Transaction::purpose => 'Strom Gutschrift Überzahlung',
            Transaction::amount => 80.00,
            Transaction::balance => 825.74,
            Transaction::balance_currency => 'EUR',
            Transaction::amount_currency => 'EUR',
        ]);

        // =====================================================================
        // GRUPPE D – SKIPPED (bereits verknüpft)
        // =====================================================================

        /**
         * #11 – Spotify bereits verknüpft
         * fixed_cost_id ist gesetzt → matchTransaction() gibt sofort 'skipped' zurück
         */
        if ($fcSpotify) {
            $this->createTransaction($userId, $bankAccountId, [
                Transaction::date => '2026-06-29',
                Transaction::value_date => '2026-06-29',
                Transaction::payer => 'Spotify AB',
                Transaction::description => 'Lastschrift SEPA',
                Transaction::purpose => 'Spotify Premium Abo Juni',
                Transaction::amount => -9.99,
                Transaction::balance => 815.75,
                Transaction::balance_currency => 'EUR',
                Transaction::amount_currency => 'EUR',
                Transaction::fixed_cost_id => $fcSpotify->id,
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createTransaction(int $userId, int $bankAccountId, array $data): void
    {
        $base = array_merge($data, [
            Transaction::user_id => $userId,
            Transaction::bank_account_id => $bankAccountId,
        ]);

        // Hash aus den Transaktionsdaten berechnen
        $base[Transaction::hash] = Transaction::createHash($base);

        Transaction::query()->firstOrCreate(
            [
                Transaction::hash => $base[Transaction::hash],
            ],
            $base,
        );
    }
}
