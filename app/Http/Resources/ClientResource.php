<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'email' => $this->email,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'motorcycles' => MotorcycleResource::collection($this->whenLoaded('motorcycles')),
            'work_orders' => WorkOrderResource::collection($this->whenLoaded('workOrders')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
