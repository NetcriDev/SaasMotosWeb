<?php

namespace App\Http\Resources;

use App\Enums\WorkOrderStatus;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof WorkOrderStatus
            ? $this->status
            : WorkOrderStatus::tryFrom((string) $this->status);

        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'branch_id' => $this->branch_id,
            'client_id' => $this->client_id,
            'motorcycle_id' => $this->motorcycle_id,
            'mechanic_id' => $this->mechanic_id,
            'maintenance_type_id' => $this->maintenance_type_id,
            'intake_reason' => $this->intake_reason,
            'affected_systems' => $this->affected_systems ?? [],
            'status' => $status?->value,
            'status_label' => $status?->label(),
            'received_at' => $this->received_at,
            'completed_at' => $this->completed_at,
            'notes' => $this->notes,
            'estimated_total' => $this->estimated_total,
            'estimated_total_formatted' => Money::format($this->estimated_total),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'client' => new ClientResource($this->whenLoaded('client')),
            'motorcycle' => new MotorcycleResource($this->whenLoaded('motorcycle')),
            'mechanic' => new TeamUserResource($this->whenLoaded('mechanic')),
            'maintenance_type' => new MaintenanceTypeResource($this->whenLoaded('maintenanceType')),
            'activities' => $this->whenLoaded('activities', fn () => $this->activities->map(fn ($activity): array => [
                'id' => $activity->id,
                'system' => $activity->system,
                'description' => $activity->description,
                'service_cost' => $activity->service_cost,
                'is_billable' => $activity->is_billable,
                'notes' => $activity->notes,
            ])->values()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
