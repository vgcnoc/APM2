<?php
$onts = \App\Models\Ont::whereNull('area_id')->whereNotNull('material_transaction_item_id')->with('materialTransactionItem.transaction')->get();
$count = 0;
foreach ($onts as $ont) {
    if ($ont->materialTransactionItem && $ont->materialTransactionItem->transaction && $ont->materialTransactionItem->transaction->area) {
        $areaName = $ont->materialTransactionItem->transaction->area;
        $area = \App\Models\Area::where('name', $areaName)->first();
        if ($area) {
            $ont->area_id = $area->id;
            $ont->save();
            $count++;
        }
    }
}
echo 'Updated ' . $count . ' ONTs' . PHP_EOL;
