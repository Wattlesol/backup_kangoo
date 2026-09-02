<?php

namespace App\Services;

use App\Events\SanadConversationUpdated;
use App\Models\Booking;
use App\Models\SanadAiInteraction;
use App\Models\SanadBuzzAlert;
use App\Models\SanadChatMessage;
use App\Models\SanadChatThread;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SanadAiFirstResponderService
{
    public const SHARED_ROLES = ['admin', 'demo_admin', 'employee', 'handyman', 'provider', 'user', 'customer'];

    public function __construct(private SanadAiRagService $rag)
    {
    }

    public function isEnabled(?Booking $booking): bool
    {
        if (!$booking) {
            return true;
        }

        return $booking->ai_first_responder_enabled !== false;
    }

    public function respondToCustomerMessage(SanadChatMessage $customerMessage, Booking $booking): ?SanadAiInteraction
    {
        if (!$this->isEnabled($booking) || $customerMessage->message_type !== 'text' || trim((string) $customerMessage->message) === '') {
            return null;
        }

        $interaction = $this->createInteraction($customerMessage->message, $booking, Auth::user(), 'customer');

        if ($this->shouldPostHandoverPrompt($interaction)) {
            $this->postHandoverPrompt($interaction, $booking);
        } else {
            $this->postAiResponse($interaction, $booking);
        }

        return $interaction;
    }

    public function createInteraction(string $question, ?Booking $booking, ?User $user, string $audience = 'customer'): SanadAiInteraction
    {
        $startedAt = microtime(true);
        $answer = $this->rag->answer($question, $booking, $audience);
        $responseMs = (int) round((microtime(true) - $startedAt) * 1000);
        $decision = $answer['ai_decision'] ?? [
            'action' => ($answer['requires_escalation'] ?? false) ? 'human_handover' : 'answer',
            'reason' => 'legacy_decision',
        ];
        $requiresEscalation = ($decision['action'] ?? null) === 'human_handover' || $answer['requires_escalation'];

        return SanadAiInteraction::create([
            'user_id' => optional($user)->id,
            'booking_id' => optional($booking)->id,
            'question' => $question,
            'answer' => $answer['answer'],
            'confidence' => $answer['confidence'],
            'requires_escalation' => $requiresEscalation,
            'status' => match ($decision['action'] ?? 'answer') {
                'human_handover' => 'handover_required',
                'unsupported_notice' => 'unsupported_service',
                default => 'answered',
            },
            'metadata' => [
                'sources' => $answer['sources'],
                'live_context' => $answer['live_context'],
                'service_catalog_context' => $answer['service_catalog_context'] ?? null,
                'ai_decision' => $decision,
                'provider' => $answer['provider_metadata'] ?? [],
                'langsmith_run_id' => $answer['langsmith_run_id'] ?? null,
                'response_ms' => $responseMs,
                'first_responder' => true,
            ],
        ]);
    }

    private function shouldPostHandoverPrompt(SanadAiInteraction $interaction): bool
    {
        $decision = data_get($interaction->metadata, 'ai_decision.action');

        return $decision === 'human_handover'
            || ($interaction->requires_escalation && $interaction->status === 'handover_required');
    }

    public function postAiResponse(SanadAiInteraction $interaction, Booking $booking): SanadChatMessage
    {
        $thread = $this->sharedThread($booking);
        $message = SanadChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => null,
            'sender_role' => 'system',
            'message' => $interaction->answer,
            'message_type' => 'ai_response',
            'ai_interaction_id' => $interaction->id,
            'visible_to' => self::SHARED_ROLES,
        ]);
        $thread->update(['last_message_at' => now()]);
        $this->broadcast($booking, 'ai.response_created', ['ai_interaction_id' => $interaction->id, 'message_id' => $message->id]);

        return $message;
    }

    public function postHandoverPrompt(SanadAiInteraction $interaction, ?Booking $booking): ?SanadChatMessage
    {
        if (!$booking) {
            return null;
        }

        $thread = $this->sharedThread($booking);
        $message = SanadChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => null,
            'sender_role' => 'system',
            'message' => "I don't have enough information on this. I can connect you with a Quick agent if you want.",
            'message_type' => 'ai_handover_prompt',
            'ai_interaction_id' => $interaction->id,
            'visible_to' => self::SHARED_ROLES,
        ]);
        $thread->update(['last_message_at' => now()]);
        $this->broadcast($booking, 'ai.handover_prompt_created', ['ai_interaction_id' => $interaction->id, 'message_id' => $message->id]);

        return $message;
    }

    public function setAiEnabled(Booking $booking, bool $enabled, User $actor): void
    {
        $booking->forceFill([
            'ai_first_responder_enabled' => $enabled,
            'ai_first_responder_disabled_by' => $enabled ? null : $actor->id,
            'ai_first_responder_disabled_at' => $enabled ? null : now(),
            'chat_owner_type' => $enabled ? 'ai' : ($booking->chat_owner_type === 'ai' ? 'sanad_team' : $booking->chat_owner_type),
            'chat_assigned_by' => $actor->id,
            'chat_assigned_at' => now(),
        ])->save();

        $this->postSystemNote($booking, 'AI first responder ' . ($enabled ? 're-enabled' : 'disconnected') . ' by ' . $this->displayName($actor) . '.');
    }

    public function assignChat(Booking $booking, string $targetType, ?User $targetUser, User $actor, ?string $note = null): void
    {
        $label = match ($targetType) {
            'sanad_team' => 'Quick team',
            'partner_team' => 'Partner team',
            'user' => $targetUser ? $this->displayName($targetUser) : 'team member',
            default => 'Quick team',
        };

        $booking->forceFill([
            'ai_first_responder_enabled' => false,
            'ai_first_responder_disabled_by' => $actor->id,
            'ai_first_responder_disabled_at' => now(),
            'chat_owner_type' => $targetType,
            'chat_owner_user_id' => $targetType === 'user' ? optional($targetUser)->id : null,
            'chat_assigned_by' => $actor->id,
            'chat_assigned_at' => now(),
            'chat_assignment_note' => $note,
        ])->save();

        $this->postCustomerAssignmentNotice($booking, $label);
        $this->createAssignmentBuzzes($booking, $targetType, $targetUser, $actor, $note);
    }

    public function handleHandoverDecision(SanadAiInteraction $interaction, string $decision, ?User $actor): array
    {
        $metadata = $interaction->metadata ?: [];
        $metadata['handover_decision'] = [
            'decision' => $decision,
            'decided_by' => optional($actor)->id,
            'decided_at' => now()->toIso8601String(),
        ];
        $interaction->metadata = $metadata;

        if ($decision === 'no') {
            $interaction->status = 'handover_declined';
            $interaction->requires_escalation = false;
            $interaction->save();

            return ['buzz_count' => 0, 'target' => null];
        }

        $interaction->status = 'handover_accepted';
        $interaction->requires_escalation = true;
        $interaction->save();

        $booking = $interaction->booking;
        if ($booking) {
            $actorUser = $actor ?: User::find($interaction->user_id) ?: User::query()->first();
            $currentOwnerType = $booking->chat_owner_type ?: 'ai';
            if ($currentOwnerType === 'ai') {
                $targetType = $booking->handymanAdded()->exists() ? 'user' : ($booking->provider_id ? 'partner_team' : 'sanad_team');
                $targetUser = $targetType === 'user' ? $booking->handymanAdded()->with('handyman')->first()?->handyman : null;
            } else {
                $targetType = $currentOwnerType;
                $targetUser = $targetType === 'user' && $booking->chat_owner_user_id ? User::find($booking->chat_owner_user_id) : null;
            }

            $this->setAiEnabled($booking, false, $actorUser);
            $this->assignChat($booking, $targetType, $targetUser, $actorUser, 'AI handover requested by customer.');
        }

        $buzzes = $this->createHandoverBuzzes($interaction);

        return ['buzz_count' => $buzzes->count(), 'target' => $booking ? $booking->chat_owner_type : 'sanad_team'];
    }

    public function createHandoverBuzzes(SanadAiInteraction $interaction): Collection
    {
        $booking = $interaction->booking;
        $recipients = $this->handoverRecipients($booking);
        $message = $this->handoverBuzzMessage($interaction, $booking);

        return $recipients->map(function (User $recipient) use ($booking, $interaction, $message) {
            return SanadBuzzAlert::create([
                'booking_id' => optional($booking)->id,
                'sender_id' => $interaction->user_id,
                'recipient_id' => $recipient->id,
                'recipient_role' => $recipient->user_type,
                'priority' => 'urgent',
                'status' => 'unread',
                'message' => $message,
            ]);
        });
    }

    public function handoverRecipients(?Booking $booking): Collection
    {
        if (!$booking) {
            return $this->sanadTeamUsers();
        }

        if ($booking->chat_owner_type === 'user' && $booking->chat_owner_user_id) {
            return User::where('id', $booking->chat_owner_user_id)->get();
        }

        if ($booking->chat_owner_type === 'partner_team' && $booking->provider_id) {
            return User::where(function ($query) use ($booking) {
                $query->where('id', $booking->provider_id)
                    ->orWhere('provider_id', $booking->provider_id);
            })->whereIn('user_type', ['provider', 'handyman'])->where('status', 1)->get();
        }

        if ($booking->chat_owner_type === 'sanad_team') {
            return $this->sanadTeamUsers();
        }

        $assigned = $booking->handymanAdded()->with('handyman')->get()->pluck('handyman')->filter()->values();
        if ($assigned->isNotEmpty()) {
            return $assigned;
        }

        if ($booking->provider_id) {
            return User::where(function ($query) use ($booking) {
                $query->where('id', $booking->provider_id)
                    ->orWhere('provider_id', $booking->provider_id);
            })->whereIn('user_type', ['provider', 'handyman'])->where('status', 1)->get();
        }

        return $this->sanadTeamUsers();
    }

    public function postSystemNote(Booking $booking, string $message): SanadChatMessage
    {
        $thread = $this->sharedThread($booking);
        $chatMessage = SanadChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => null,
            'sender_role' => 'system',
            'message' => $message,
            'message_type' => 'system_note',
            'visible_to' => self::SHARED_ROLES,
        ]);
        $thread->update(['last_message_at' => now()]);
        $this->broadcast($booking, 'chat.system_note_created', ['message_id' => $chatMessage->id]);

        return $chatMessage;
    }

    private function postCustomerAssignmentNotice(Booking $booking, string $assigneeLabel): SanadChatMessage
    {
        $thread = $this->sharedThread($booking);
        $message = SanadChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => null,
            'sender_role' => 'system',
            'message' => 'Your chat has been assigned to ' . $assigneeLabel . '. Please wait for them.',
            'message_type' => 'system_note',
            'visible_to' => ['customer', 'user'],
        ]);
        $thread->update(['last_message_at' => now()]);
        $this->broadcast($booking, 'chat.assignment_notice_created', ['message_id' => $message->id]);

        return $message;
    }

    private function createAssignmentBuzzes(Booking $booking, string $targetType, ?User $targetUser, User $actor, ?string $note): Collection
    {
        $recipients = $targetType === 'user' && $targetUser
            ? collect([$targetUser])
            : ($targetType === 'partner_team' ? $this->partnerTeamUsers($booking) : $this->sanadTeamUsers());

        $reference = $booking->sanad_reference ?: ('#' . $booking->id);
        $customer = optional($booking->customer)->display_name ?: optional($booking->customer)->email ?: 'Customer';
        $message = "Chat assignment request.\nRequest: {$reference}\nCustomer: {$customer}\nAssigned by: " . $this->displayName($actor) . "\nPlease accept to entertain the customer.";
        if ($note) {
            $message .= "\nNote: {$note}";
        }

        return $recipients->map(function (User $recipient) use ($booking, $actor, $message) {
            $buzz = SanadBuzzAlert::create([
                'booking_id' => $booking->id,
                'sender_id' => $actor->id,
                'recipient_id' => $recipient->id,
                'recipient_role' => $recipient->user_type,
                'priority' => 'urgent',
                'status' => 'unread',
                'message' => $message,
                'action_type' => 'chat_assignment_accept',
                'action_status' => 'pending',
            ]);

            Notification::create([
                'id' => Str::random(32),
                'type' => 'sanad_chat_assignment',
                'notifiable_type' => User::class,
                'notifiable_id' => $recipient->id,
                'data' => json_encode([
                    'type' => 'sanad_chat_assignment',
                    'id' => $booking->id,
                    'buzz_id' => $buzz->id,
                    'subject' => 'Chat assigned to you',
                    'message' => $message,
                ]),
            ]);

            return $buzz;
        });
    }

    private function partnerTeamUsers(Booking $booking): Collection
    {
        if (!$booking->provider_id) {
            return collect();
        }

        return User::query()
            ->where('status', 1)
            ->where(function ($query) use ($booking) {
                $query->where('id', $booking->provider_id)
                    ->orWhere('provider_id', $booking->provider_id);
            })
            ->get();
    }

    private function sharedThread(Booking $booking): SanadChatThread
    {
        return SanadChatThread::firstOrCreate([
            'booking_id' => $booking->id,
            'thread_type' => 'shared',
        ], [
            'participant_roles' => self::SHARED_ROLES,
            'created_by' => Auth::id(),
            'status' => 'open',
            'last_message_at' => now(),
        ]);
    }

    private function sanadTeamUsers(): Collection
    {
        return User::where(function ($query) {
                $query->whereIn('user_type', ['admin', 'demo_admin', 'handyman'])
                    ->orWhereHas('roles', fn ($roleQuery) => $roleQuery->whereIn('name', ['admin', 'demo_admin', 'employee', 'handyman']));
            })
            ->where(function ($query) {
                $query->whereNull('provider_id')->orWhere('provider_id', 0);
            })
            ->where('status', 1)
            ->get();
    }

    private function handoverBuzzMessage(SanadAiInteraction $interaction, ?Booking $booking): string
    {
        $reference = $booking ? ($booking->sanad_reference ?: '#' . $booking->id) : 'General inquiry';
        $customer = optional($interaction->user)->display_name ?: optional($interaction->user)->email ?: 'Customer';
        $confidence = round(($interaction->confidence ?? 0) * 100);

        return "AI handover requested.\nRequest: {$reference}\nCustomer: {$customer}\nConfidence: {$confidence}%\nQuestion: {$interaction->question}";
    }

    private function displayName(User $user): string
    {
        return $user->display_name ?: $user->first_name ?: $user->email ?: 'User';
    }

    private function broadcast(Booking $booking, string $type, array $payload = []): void
    {
        try {
            broadcast(new SanadConversationUpdated($booking->id, $type, $payload))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
