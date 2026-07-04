<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MotorcycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'client_id' => $this->client_id,
            'branch_id' => $this->branch_id,
            'license_plate' => $this->license_plate,
            'brand_id' => $this->brand_id,
            'motorcycle_model_id' => $this->motorcycle_model_id,
            'year' => $this->year,
            'client' => new ClientResource($this->whenLoaded('client')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'motorcycle_model' => new MotorcycleModelResource($this->whenLoaded('motorcycleModel')),
            'work_orders' => WorkOrderResource::collection($this->whenLoaded('workOrders')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
