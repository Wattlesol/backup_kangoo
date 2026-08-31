<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\SanadPartnerServicePerformance;
use App\Models\SanadPartnerServiceAvailability;
use App\Models\Service;
use App\Models\User;
use App\Models\ProviderDocument;
use App\Models\Documents;

class SanadAssignmentService
{
    public function candidates(Booking $booking)
    {
        $requiredDocumentIds = Documents::where('status', 1)->where('is_required', 1)->pluck('id');
        $providerQuery = User::where('user_type', 'provider')->where('status', 1);

        $verifiedProviders = (clone $providerQuery)
            ->whereDoesntHave('providerDocument', function ($query) use ($requiredDocumentIds) {
                $query->whereIn('document_id', $requiredDocumentIds)->where('is_verified', 0);
            })
            ->when($requiredDocumentIds->isNotEmpty(), function ($query) use ($requiredDocumentIds) {
                $query->whereHas('providerDocument', function ($documentQuery) use ($requiredDocumentIds) {
                    $documentQuery->whereIn('document_id', $requiredDocumentIds)->where('is_verified', 1);
                });
            })
            ->get();

        $pool = $verifiedProviders->isNotEmpty() ? $verifiedProviders : $providerQuery->get();

        // Respect partner's choice to offer services
        if ($booking->service_id) {
            $serviceId = (int) $booking->service_id;

            // Check partners who explicitly disabled or enabled this service
            $disabledPartnerIds = SanadPartnerServiceAvailability::where('service_id', $serviceId)
                ->where('is_enabled', 0)
                ->pluck('provider_id')
                ->toArray();

            $enabledPartnerIds = SanadPartnerServiceAvailability::where('service_id', $serviceId)
                ->where('is_enabled', 1)
                ->pluck('provider_id')
                ->toArray();

            // Check direct service owner if applicable
            $service = Service::find($serviceId);
            if ($service && !empty($service->provider_id)) {
                $enabledPartnerIds[] = (int) $service->provider_id;
            }

            // 1. Exclude any partner who opted out / disabled this service
            if (!empty($disabledPartnerIds)) {
                $pool = $pool->whereNotIn('id', $disabledPartnerIds);
            }

            // 2. If partners have explicitly enabled this service, filter to only those partners
            if (!empty($enabledPartnerIds)) {
                $matchingPool = $pool->whereIn('id', $enabledPartnerIds);
                if ($matchingPool->isNotEmpty()) {
                    $pool = $matchingPool;
                }
            }
        }

        return $pool->map(function (User $partner) use ($booking) {
            $performance = SanadPartnerServicePerformance::where('provider_id', $partner->id)
                ->where('service_id', $booking->service_id)->first();
            $active = Booking::where('provider_id', $partner->id)
                ->whereNotIn('sanad_stage', ['completed', 'closed'])
                ->where('status', '!=', 'cancelled')->count();
            $capacity = (int) ($partner->sanad_daily_capacity ?: 10);
            $capacityScore = $capacity > 0 ? max(0, min(100, 100 - (($active / $capacity) * 100))) : 50;
            $avg = (float) ($performance?->average_completion_minutes ?? $partner->sanad_average_completion_minutes ?? 35);
            $speed = $avg ? max(0, 100 - min(100, ($avg / 1440) * 100)) : 50;
            $quality = (float) ($performance?->quality_score ?? $partner->sanad_quality_score ?? 98);
            $sla = (float) ($performance?->sla_compliance_rate ?? $partner->sanad_sla_compliance_rate ?? 99.4);
            $acceptance = (float) ($performance?->acceptance_rate ?? $partner->sanad_acceptance_rate ?? 96.5);
            $cancellation = (float) ($performance?->cancellation_rate ?? $partner->sanad_cancellation_rate ?? 1.2);
            $experience = (int) ($performance?->completed_orders ?? Booking::where('provider_id', $partner->id)
                ->where('service_id', $booking->service_id)->whereIn('sanad_stage', ['completed', 'closed'])->count());
            $score = ($experience * 5) + ($quality * .25) + ($sla * .2) + ($acceptance * .1)
                + ($capacityScore * .2) + ($speed * .1) - ($cancellation * .1) - ($active * 2);
            
            $normalizedScore = min(99, max(85, round($score)));
            $partner->assignment_score = $normalizedScore;
            $partner->assignment_metrics = compact('quality', 'sla', 'acceptance', 'cancellation', 'active', 'capacity', 'capacityScore', 'avg', 'speed', 'experience');
            return $partner;
        })->filter(fn ($partner) => $partner->assignment_metrics['capacity'] === 0 || $partner->assignment_metrics['active'] < $partner->assignment_metrics['capacity'])
        ->sortByDesc('assignment_score')->values();
    }
}

