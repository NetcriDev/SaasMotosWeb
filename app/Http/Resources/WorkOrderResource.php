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
            'maintenance_type_id' => $this->maintenance_type_id,
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
            'maintenance_type' => new MaintenanceTypeResource($this->whenLoaded('maintenanceType')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
