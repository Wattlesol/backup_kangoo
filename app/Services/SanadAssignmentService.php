<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\SanadPartnerServicePerformance;
use App\Models\User;
use App\Models\ProviderDocument;
use App\Models\Documents;

class SanadAssignmentService
{
    public function candidates(Booking $booking)
    {
        $requiredDocumentIds = Documents::where('status', 1)->where('is_required', 1)->pluck('id');
        return User::where('user_type', 'provider')->where('status', 1)
            ->whereDoesntHave('providerDocument', function ($query) use ($requiredDocumentIds) {
                $query->whereIn('document_id', $requiredDocumentIds)->where('is_verified', 0);
            })
            ->when($requiredDocumentIds->isNotEmpty(), function ($query) use ($requiredDocumentIds) {
                $query->whereHas('providerDocument', function ($documentQuery) use ($requiredDocumentIds) {
                    $documentQuery->whereIn('document_id', $requiredDocumentIds)->where('is_verified', 1);
                });
            })
            ->get()->map(function (User $partner) use ($booking) {
                $performance = SanadPartnerServicePerformance::where('provider_id', $partner->id)
                    ->where('service_id', $booking->service_id)->first();
                $active = Booking::where('provider_id', $partner->id)
                    ->whereNotIn('sanad_stage', ['completed', 'closed'])
                    ->where('status', '!=', 'cancelled')->count();
                $capacity = (int) ($partner->sanad_daily_capacity ?: 0);
                $capacityScore = $capacity > 0 ? max(0, min(100, 100 - (($active / $capacity) * 100))) : 50;
                $avg = (float) ($performance?->average_completion_minutes ?? $partner->sanad_average_completion_minutes ?? 0);
                $speed = $avg ? max(0, 100 - min(100, ($avg / 1440) * 100)) : 50;
                $quality = (float) ($performance?->quality_score ?? $partner->sanad_quality_score ?? 0);
                $sla = (float) ($performance?->sla_compliance_rate ?? $partner->sanad_sla_compliance_rate ?? 0);
                $acceptance = (float) ($performance?->acceptance_rate ?? $partner->sanad_acceptance_rate ?? 0);
                $cancellation = (float) ($performance?->cancellation_rate ?? $partner->sanad_cancellation_rate ?? 0);
                $experience = (int) ($performance?->completed_orders ?? Booking::where('provider_id', $partner->id)
                    ->where('service_id', $booking->service_id)->whereIn('sanad_stage', ['completed', 'closed'])->count());
                $score = ($experience * 5) + ($quality * .25) + ($sla * .2) + ($acceptance * .1)
                    + ($capacityScore * .2) + ($speed * .1) - ($cancellation * .1) - ($active * 2);
                $partner->assignment_score = round($score, 2);
                $partner->assignment_metrics = compact('quality', 'sla', 'acceptance', 'cancellation', 'active', 'capacity', 'capacityScore', 'avg', 'speed', 'experience');
                return $partner;
            })->filter(fn ($partner) => $partner->assignment_metrics['capacity'] === 0 || $partner->assignment_metrics['active'] < $partner->assignment_metrics['capacity'])
            ->sortByDesc('assignment_score')->values();
    }
}
