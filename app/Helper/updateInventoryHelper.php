<?php

namespace App\Helper;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use Carbon\Carbon;

class updateInventoryHelper
{
    public function updateQtyBad(){
        
    }

    public static function updateInventory($payload)
    {
        $now = Carbon::now();
        $user = auth()->user();

        $warehouseIds = $payload['warehouse_ids'];
        $productSkuIds = $payload['product_sku_ids'];
        $details = $payload['details'];
        $referenceCode = $payload['reference_code'];

        $origin = $payload['origin'];
        $type = $payload['type'];


        $createdStockMovement = [];
        $findInventories = Inventory::where('deleted_at', null)->whereIn('product_sku_id', $productSkuIds)->whereIn('warehouse_id', $warehouseIds)->get();
        $findInventories = collect($findInventories)->toArray();

        if (count($details) > 0) {
            /** let's update the key */
            foreach ($details as $inventoryItemKey => $inventoryItem) {
                $findRelated = array_filter($findInventories, function ($item) use ($inventoryItem) {
                    if ($item['warehouse_id'] === $inventoryItem['warehouse_id'] && $item['product_sku_id'] === $inventoryItem['product_sku_id']) {
                        return $item;
                    }
                });
                if (count($findRelated) > 0) {
                    if ($type === "IN") {
                        $createdStockMovement[] = [
                            'origin' => $origin,
                            'type' => $type,
                            'reference_code' => $referenceCode,
                            'before_qty' => $findInventories[$inventoryItemKey]['qty'],
                            'after_qty' => $findInventories[$inventoryItemKey]['qty'] + $findRelated[0]['qty'],
                            'usage_qty' => $findRelated[0]['qty'],
                            'warehouse_id' => $findRelated[0]['warehouse_id'],
                            'product_sku_id' => $findRelated[0]['product_sku_id'],
                            'created_by_id'=>$user->id,

                            'created_at' => $now,
                            'updated_at' => null,
                            'deleted_at' => null
                        ];

                        $findInventories[$inventoryItemKey]['qty'] += $findRelated[0]['qty'];
                    } else if ($type === "ADJUSTMENT") {
                        $createdStockMovement[] = [
                            'origin' => $origin,
                            'type' => $type,
                            'reference_code' => $referenceCode,
                            'before_qty' => $findInventories[$inventoryItemKey]['qty'],
                            'after_qty' => $findRelated[0]['qty'],
                            'usage_qty' => $findRelated[0]['qty'],
                            'warehouse_id' => $findRelated[0]['warehouse_id'],
                            'created_by_id' => $user->id,

                            'product_sku_id' => $findRelated[0]['product_sku_id'],
                            'created_at' => $now,
                            'updated_at' => null,
                            'deleted_at' => null
                        ];
                        $findInventories[$inventoryItemKey]['qty'] = intval($findRelated[0]['qty']);
                    } else {
                        $findInventories[$inventoryItemKey]['qty'] = $findRelated[0]['qty'];
                    }
                } else {
                    if ($type === 'IN') {
                        $createdStockMovement[] = [
                            'origin' => $origin,
                            'type' => $type,
                            'reference_code' => $referenceCode,
                            'before_qty' => $findInventories[$inventoryItemKey]['qty'],
                            'after_qty' => $findRelated[0]['qty'],
                            'usage_qty' => $findRelated[0]['qty'],
                            'warehouse_id' => $findRelated[0]['warehouse_id'],
                            'product_sku_id' => $findRelated[0]['product_sku_id'],
                            'created_by_id' => $user->id,

                            'created_at' => $now,
                            'updated_at' => null,
                            'deleted_at' => null
                        ];
                        $findInventories[] = [
                            'warehouse_id' => $inventoryItem['warehouse_id'],
                            'product_sku_id' => $inventoryItem['product_sku_id'],
                            'qty' => $inventoryItem['qty'],
                            'created_by_id' => $user->id,
                            'created_at' => $now,
                            'updated_at' => null,
                            'deleted_at' => null
                        ];
                    } else {
                        return [
                            'message' => "Stok Tidak Mencukupi Atau Tidak Ada Stok Untuk Produk",
                            'status' => 'error'
                        ];
                    }
                }
            }
        }

        Inventory::updateOrCreate($findInventories);
        InventoryMovement::updateOrCreate($createdStockMovement);

        return [
            'message' => "Berhasil Mengupdate Stok",
            'status' => 'success'
        ];
    }

}