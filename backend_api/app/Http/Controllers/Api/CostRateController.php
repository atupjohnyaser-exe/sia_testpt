<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCostRatesRequest;
use App\Http\Resources\CostRateResource;
use App\Models\CostRate;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class CostRateController extends Controller
{
    public function index()
    {
        return CostRateResource::collection(CostRate::orderBy('id')->get())->additional([
            // The manhunt form uses this to show an estimate before saving.
            'meta' => ['bounty_multiplier' => (float) config('rockport.manhunt_bounty_multiplier')],
        ]);
    }

    /**
     * Changes apply to manhunts logged from now on. Stored manhunts keep the cost
     * they were logged with.
     */
    public function update(UpdateCostRatesRequest $request, AuditLogger $audit)
    {
        $changes = [];

        DB::transaction(function () use ($request, &$changes) {
            foreach ($request->validated('rates') as $row) {
                $rate = CostRate::where('code', $row['code'])->firstOrFail();

                if ($rate->unit_cost !== (int) $row['unit_cost']) {
                    $changes[$rate->code] = ['from' => $rate->unit_cost, 'to' => (int) $row['unit_cost']];
                    $rate->update(['unit_cost' => (int) $row['unit_cost'], 'updated_by' => $request->user()->id]);
                }
            }
        });

        if ($changes !== []) {
            $audit->record(AuditAction::RatesUpdated, null, ['changes' => $changes]);
        }

        return CostRateResource::collection(CostRate::orderBy('id')->get());
    }
}
