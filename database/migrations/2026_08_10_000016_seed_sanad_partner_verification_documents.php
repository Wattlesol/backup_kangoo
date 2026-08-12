<?php

use App\Models\Documents;
use App\Models\ProviderDocument;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

class SeedSanadPartnerVerificationDocuments extends Migration
{
    private array $documents = [
        'Commercial Registration',
        'Business License',
        'VAT Certificate',
        'IBAN / Bank Proof',
        'Authorization Letter',
    ];

    public function up()
    {
        foreach ($this->documents as $name) {
            Documents::withTrashed()->updateOrCreate(
                ['name' => $name],
                ['status' => 1, 'is_required' => 1, 'deleted_at' => null]
            );
        }

        $documentIds = Documents::whereIn('name', $this->documents)->pluck('id');
        User::where('user_type', 'provider')->chunkById(100, function ($providers) use ($documentIds) {
            foreach ($providers as $provider) {
                foreach ($documentIds as $documentId) {
                    ProviderDocument::withTrashed()->updateOrCreate(
                        ['provider_id' => $provider->id, 'document_id' => $documentId],
                        ['is_verified' => 0, 'deleted_at' => null]
                    );
                }
            }
        });
    }

    public function down()
    {
        $documentIds = Documents::whereIn('name', $this->documents)->pluck('id');
        ProviderDocument::whereIn('document_id', $documentIds)->where('is_verified', 0)->delete();
    }
}
