<?php

namespace App\Services;

use App\Models\CostRate;

/**
 * Cost to state is always quantity x unit price. Nothing is typed in by hand.
 */
class CostToStateCalculator
{
    /** Manhunt columns that are counted. Each one has a row in cost_rates with the same code. */
    public const QUANTITY_FIELDS = [
        'officers_involved',
        'officers_injured',
        'units_stood_down',
        'checkpoints',
        'k9_units',
        'helicopters',
        'property_damage_incidents',
        'insurance_claims',
    ];

    /** The most one manhunt may record for each. Stops typing errors and abuse. */
    public const QUANTITY_LIMITS = [
        'officers_involved' => 5000,
        'officers_injured' => 5000,
        'units_stood_down' => 5000,
        'checkpoints' => 500,
        'k9_units' => 200,
        'helicopters' => 50,
        'property_damage_incidents' => 5000,
        'insurance_claims' => 5000,
    ];

    /**
     * @param  array<string,int>  $quantities  keyed by rate code
     * @return array{lines: array<int,array<string,mixed>>, total: int}
     */
    public function breakdown(array $quantities): array
    {
        $lines = [];
        $total = 0;

        foreach (CostRate::query()->orderBy('id')->get() as $rate) {
            $quantity = (int) ($quantities[$rate->code] ?? 0);

            if ($quantity <= 0) {
                continue;
            }

            $cost = $quantity * $rate->unit_cost;
            $total += $cost;

            $lines[] = [
                'code' => $rate->code,
                'label' => $rate->label,
                'qty' => $quantity,
                'unit_cost' => $rate->unit_cost,
                'cost' => $cost,
            ];
        }

        return ['lines' => $lines, 'total' => $total];
    }

    /**
     * Adds up the stored breakdowns of several manhunts into one table.
     *
     * @param  iterable<\App\Models\Manhunt>  $manhunts
     * @return array{lines: array<int,array<string,mixed>>, total: int}
     */
    public function aggregate(iterable $manhunts): array
    {
        $lines = [];

        foreach ($manhunts as $manhunt) {
            foreach ($manhunt->cost_breakdown ?? [] as $line) {
                $code = $line['code'];

                $lines[$code] ??= ['code' => $code, 'label' => $line['label'], 'qty' => 0, 'cost' => 0];
                $lines[$code]['qty'] += $line['qty'];
                $lines[$code]['cost'] += $line['cost'];
            }
        }

        return [
            'lines' => array_values($lines),
            'total' => (int) array_sum(array_column($lines, 'cost')),
        ];
    }
}
