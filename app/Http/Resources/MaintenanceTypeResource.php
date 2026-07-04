<?php

namespace App\Http\Resources;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'price_formatted' => Money::format($this->price),
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
