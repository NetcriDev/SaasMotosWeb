<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesTeamAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BranchController extends Controller
{
    use AuthorizesTeamAccess;

    public function index(Request $request, Team $team): AnonymousResourceCollection
    {
        $this->authorizeTeam($request, $team);

        return BranchResource::collection(
            $team->branches()
                ->orderBy('name')
                ->get(),
        );
    }
}
