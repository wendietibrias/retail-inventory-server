<?php

namespace App\Helper;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use Carbon\Carbon;
use Log;

class updateInventoryHelper
{
    public function updateQtyBad()
    {

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


        $createdInventories = [];
        $createdStockMovement = [];
        $findInventories = Inventory::where('deleted_at', null)->whereIn('product_sku_id', $productSkuIds)->whereIn('warehouse_id', $warehouseIds)->get();
        $findInventories = collect($findInventories)->toArray();


        if (count($details) > 0) {
            /** let's update the key */
            foreach ($details as $inventoryItemKey => $inventoryItem) {
                $findRelated = array_find($findInventories, function ($item) use ($inventoryItem, $warehouseIds) {
                    if ($item['warehouse_id'] == $warehouseIds[0] && $item['product_sku_id'] == $inventoryItem['product_sku_id']) {
                        return $item;
                    }
                });

                Log::info($findInventories);
                if ($findRelated) {
                    if ($type === "IN") {
                        $createdStockMovement[] = [
                            'origin' => $origin,
                            'type' => $type,
                            'reference' => $referenceCode,
                            'before_qty' => $findInventories[$inventoryItemKey]['qty'],
                            'after_qty' => $findInventories[$inventoryItemKey]['qty'] + $findRelated['qty'],
                            'usage_qty' => $findRelated['qty'],
                            'warehouse_id' => $findRelated['warehouse_id'],
                            'product_sku_id' => $findRelated['product_sku_id'],

                            'created_at' => $now,
                            'updated_at' => null,
                            'deleted_at' => null
                        ];

                        $findInventories[$inventoryItemKey]['qty'] += intval($inventoryItem['qty']);
                    } else if ($type === "ADJUSTMENT") {
                        $createdStockMovement[] = [
                            'origin' => $origin,
                            'type' => $type,
                            'reference' => $referenceCode,
                            'before_qty' => $findInventories[$inventoryItemKey]['qty'],
                            'after_qty' => $findRelated['qty'],
                            'usage_qty' => $findRelated['qty'],
                            'warehouse_id' => $findRelated['warehouse_id'],

                            'product_sku_id' => $findRelated['product_sku_id'],
                            'created_at' => $now,
                            'updated_at' => null,
                            'deleted_at' => null
                        ];
                        $findInventories[$inventoryItemKey]['qty'] = intval($inventoryItem['qty']);
                    } else {
                        if ($findRelated['qty'] < $inventoryItem['qty']) {
                            return [
                                'message' => "Stok Tidak Mencukupi Atau Tidak Ada Stok Untuk Produk",
                                'status' => 'error'
                            ];
                        }
                        $createdStockMovement[] = [
                            'origin' => $origin,
                            'type' => $type,
                            'reference' => $referenceCode,
                            'before_qty' => $findInventories[$inventoryItemKey]['qty'],
                            'after_qty' => $findInventories[$inventoryItemKey]['qty'] - $inventoryItem['qty'],
                            'usage_qty' => $inventoryItem['qty'],
                            'warehouse_id' => $findRelated['warehouse_id'],
                            'product_sku_id' => $findRelated['product_sku_id'],

                            'created_at' => $now,
                            'updated_at' => null,
                            'deleted_at' => null
                        ];

                        $findInventories[$inventoryItemKey]['qty'] -= $inventoryItem['qty'];
                    }
                } else {
                    if ($type === 'IN') {
                        $createdStockMovement[] = [
                            'origin' => $origin,
                            'type' => $type,
                            'reference' => $referenceCode,
                            'before_qty' => 0,
                            'after_qty' => $inventoryItem['qty'],
                            'usage_qty' => $inventoryItem['qty'],
                            'warehouse_id' => $warehouseIds[0],
                            'product_sku_id' => $inventoryItem['product_sku_id'],
                            'created_at' => $now,
                            'updated_at' => null,
                            'deleted_at' => null
                        ];
                        $createdInventories[] = [
                            'warehouse_id' => $warehouseIds[0],
                            'product_sku_id' => $inventoryItem['product_sku_id'],
                            'qty' => $inventoryItem['qty'],
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

        if (count($findInventories) > 0) {
            foreach ($findInventories as $inventory) {
                Inventory::where('product_sku_id', $inventory['product_sku_id'])->where('warehouse_id', $inventory['warehouse_id'])->update([
                    'qty' => $inventory['qty']
                ]);
            }
        }

        if (count($createdInventories) > 0) {
            Inventory::insert($createdInventories);
        }

        InventoryMovement::insert($createdStockMovement);

        return [
            'message' => "Berhasil Mengupdate Stok",
            'status' => 'success'
        ];
    }

}