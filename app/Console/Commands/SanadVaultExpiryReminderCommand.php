<?php

namespace App\Console\Commands;

use App\Models\SanadBuzzAlert;
use App\Models\SanadChatMessage;
use App\Models\SanadChatThread;
use App\Models\SanadDocumentVaultItem;
use Illuminate\Console\Command;

class SanadVaultExpiryReminderCommand extends Command
{
    protected $signature = 'sanad:vault-expiry-reminders';

    protected $description = 'Send user-only Sanad AI buzz reminders for vault documents nearing expiry.';

    public function handle(): int
    {
        $documents = SanadDocumentVaultItem::query()
            ->where('source', 'vault')
            ->whereNotNull('owner_id')
            ->whereNotNull('expiry_date')
            ->whereNotNull('expiry_reminder_at')
            ->where('expiry_reminder_enabled', true)
            ->whereNull('expiry_reminder_sent_at')
            ->whereDate('expiry_reminder_at', '<=', now()->toDateString())
            ->get();

        foreach ($documents as $document) {
            $message = sprintf(
                'Sanad AI reminder: Your %s document expires on %s. Please renew it and replace the document in your vault so it stays ready for future requests.',
                $document->document_type,
                optional($document->expiry_date)->format('Y-m-d')
            );

            $buzz = SanadBuzzAlert::create([
                'booking_id' => null,
                'sender_id' => null,
                'recipient_id' => $document->owner_id,
                'recipient_role' => 'user',
                'priority' => 'high',
                'status' => 'unread',
                'message' => $message,
                'action_type' => 'vault_document_expiry_reminder',
                'action_status' => 'pending',
            ]);

            $thread = SanadChatThread::firstOrCreate([
                'booking_id' => null,
                'thread_type' => 'sanad_ai_vault_' . $document->owner_id,
            ], [
                'participant_roles' => ['user', 'customer'],
                'created_by' => $document->owner_id,
                'status' => 'open',
            ]);

            SanadChatMessage::create([
                'thread_id' => $thread->id,
                'sender_id' => null,
                'sender_role' => 'system',
                'recipient_id' => $document->owner_id,
                'message' => $message,
                'visible_to' => ['user', 'customer'],
                'message_type' => 'buzz',
                'buzz_alert_id' => $buzz->id,
            ]);

            $thread->forceFill(['last_message_at' => now()])->save();
            $document->forceFill(['expiry_reminder_sent_at' => now()])->save();
        }

        $this->info("Sent {$documents->count()} vault expiry reminder(s).");

        return self::SUCCESS;
    }
}
