<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesTeamAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Http\Resources\MaintenanceTypeResource;
use App\Http\Resources\MotorcycleModelResource;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogController extends Controller
{
    use AuthorizesTeamAccess;

    public function brands(Request $request, Team $team): AnonymousResourceCollection
    {
        $this->authorizeTeam($request, $team);

        return BrandResource::collection(
            $team->brands()
                ->orderBy('name')
                ->get(),
        );
    }

    public function motorcycleModels(Request $request, Team $team): AnonymousResourceCollection
    {
        $this->authorizeTeam($request, $team);

        return MotorcycleModelResource::collection(
            $team->motorcycleModels()
                ->with('brand')
                ->when($request->integer('brand_id'), fn ($query, int $brandId) => $query->where('brand_id', $brandId))
                ->orderBy('name')
                ->get(),
        );
    }

    public function maintenanceTypes(Request $request, Team $team): AnonymousResourceCollection
    {
        $this->authorizeTeam($request, $team);

        return MaintenanceTypeResource::collection(
            $team->maintenanceTypes()
                ->orderBy('name')
                ->get(),
        );
    }
}
