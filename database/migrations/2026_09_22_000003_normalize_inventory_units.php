<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $labels = [
            'l' => ['name' => 'Lit', 'symbol' => 'lit'],
            'thung' => ['name' => 'Thung', 'symbol' => 'thung'],
            'goi' => ['name' => 'Goi', 'symbol' => 'goi'],
            'cai' => ['name' => 'Cai', 'symbol' => 'cai'],
            'phan' => ['name' => 'Phan', 'symbol' => 'phan'],
        ];
        foreach ($labels as $code => $values) DB::table('units')->where('code', $code)->update($values + ['updated_at' => now()]);

        $unitIds = DB::table('units')->pluck('id', 'code');
        foreach (DB::table('inventory_items')->orderBy('id')->get() as $item) {
            $normalized = mb_strtolower(Str::ascii(trim((string) $item->unit)));
            $code = match ($normalized) {
                'lit', 'liter', 'litre' => 'l',
                'goi' => 'goi',
                'thung' => 'thung',
                'cai' => 'cai',
                'phan' => 'phan',
                default => $normalized,
            };
            if (isset($unitIds[$code])) DB::table('inventory_items')->where('id', $item->id)->update(['base_unit_id' => $unitIds[$code]]);
        }
        DB::table('recipe_items')->orderBy('id')->each(function ($line): void {
            DB::table('recipe_items')->where('id', $line->id)->update(['unit_id' => DB::table('inventory_items')->where('id', $line->inventory_item_id)->value('base_unit_id')]);
        });
        $used = DB::table('inventory_items')->whereNotNull('base_unit_id')->pluck('base_unit_id')
            ->merge(DB::table('recipe_items')->whereNotNull('unit_id')->pluck('unit_id'))
            ->merge(DB::table('inventory_transaction_items')->whereNotNull('unit_id')->pluck('unit_id'))->unique();
        DB::table('units')->whereIn('code', ['l-t', 'g-i'])->whereNotIn('id', $used)->delete();
    }

    public function down(): void
    {
        // Normalization is intentionally not reversed; no business history is changed.
    }
};
