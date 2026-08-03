<?php

use App\Models\Contracts\Documentables;
use App\Models\Document;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(Documentables::TABLE, function (Blueprint $table) {
            $table->foreignId(Document::documentable_document_id)
                ->constrained(Document::TABLE)
                ->cascadeOnDelete();
            $table->string(Document::documentable_type);
            $table->unsignedBigInteger(Document::documentable_id);

            $table->unique([
                Document::documentable_document_id,
                Document::documentable_type,
                Document::documentable_id,
            ], 'documentable_unique');

            $table->index([
                Document::documentable_type,
                Document::documentable_id,
            ], 'documentable_owner_index');
        });

        $this->associateOldModelsToDocument();

        Schema::table(Document::TABLE, function (Blueprint $table) {
            $table->dropColumn(['documentable_type', 'documentable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(Document::TABLE, function (Blueprint $table) {
            $table->string('documentable_type')->nullable()->after(Document::sort);
            $table->unsignedBigInteger('documentable_id')->nullable()->after('documentable_type');
        });

        $this->deAssociateOldModelsToDocument();

        Schema::dropIfExists(Documentables::TABLE);
    }

    /**
     * @return void
     */
    private function associateOldModelsToDocument(): void
    {
        DB::table(Document::TABLE)
            ->whereNotNull('documentable_type')
            ->whereNotNull('documentable_id')
            ->orderBy(Document::id)
            ->chunkById(500, function ($documents): void {
                $rows = [];

                foreach ($documents as $document) {
                    $rows[] = [
                        Document::documentable_document_id => $document->{Document::id},
                        Document::documentable_type => $document->{'documentable_type'},
                        Document::documentable_id => $document->{'documentable_id'},
                        Document::created_at => $document->{Document::created_at} ?? now(),
                        Document::updated_at => $document->{Document::updated_at} ?? now(),
                    ];
                }

                if (!empty($rows)) {
                    DB::table(Documentables::TABLE)->upsert(
                        $rows,
                        [
                            Document::documentable_document_id,
                            Document::documentable_type,
                            Document::documentable_id,
                        ],
                    );
                }
            }, Document::id);
    }

    /**
     * @return void
     */
    private function deAssociateOldModelsToDocument(): void
    {
        $firstLinksByDocument = DB::table(Documentables::TABLE)
            ->select([
                Document::documentable_document_id,
                Document::documentable_type,
                Document::documentable_id,
            ])
            ->get()
            ->unique(Document::documentable_document_id)
            ->values();

        foreach ($firstLinksByDocument as $link) {
            DB::table(Document::TABLE)
                ->where(Document::id, $link->{Document::documentable_document_id})
                ->update([
                    'documentable_type' => $link->{Document::documentable_type},
                    'documentable_id' => $link->{Document::documentable_id},
                ]);
        }
    }
};

