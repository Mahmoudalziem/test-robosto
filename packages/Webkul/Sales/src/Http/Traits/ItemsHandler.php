<?php

namespace Webkul\Sales\Http\Traits;

use Webkul\Area\Models\Area;
use Webkul\Core\Models\Channel;
use Webkul\Bundle\Models\Bundle;
use Webkul\Product\Models\Product;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Inventory\Models\InventoryProduct;
use Webkul\Inventory\Models\InventoryWarehouse;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;

trait ItemsHandler {

    public function getMergedItems(array $items) {

        Log::info('Start Original Items');
        Log::info($items);

        $data['bundle_items'] = [];
        $mergedItems = [];
        $mainItems = [];
        $newBundleItems = [];

        foreach ($items as $item) {

            $product = Product::find($item['id']);

            if ($product->bundle_id) {
                $bundleItems = $product->bundle->items;
                foreach ($bundleItems as $bundleItem) {
                    $data['bundle_items'][$product->id][] = ['id' => $bundleItem['product_id'], 'qty' => $bundleItem['quantity'] * $item['qty']];
                }
            } else {
                $mainItems[$product->id] = ['id' => $item['id'], 'qty' => $item['qty']];
            }
        }

        $itemBundleQty[] = 0;

        foreach ($data['bundle_items'] as $key => $items) {
            foreach ($items as $item) {
                if (!isset($newBundleItems[$item['id']])) {

                    $itemBundleQty[$item['id']] = !isset($itemBundleQty[$item['id']]) ? $item['qty'] : $itemBundleQty[$item['id']];
                    $newBundleItems[$item['id']] = ['id' => $item['id'], 'qty' => $itemBundleQty[$item['id']]];
                } else {
                    $itemBundleQty[$item['id']] = $itemBundleQty[$item['id']] + $item['qty'];
                    unset($newBundleItems[$item['id']]);
                    $newBundleItems[$item['id']] = ['id' => $item['id'], 'qty' => ($itemBundleQty[$item['id']])];
                }
            }
        }

        $mergedItemQty[] = 0;

        if (count($mainItems) > 0 && count($newBundleItems) == 0) {
            $mergedItems = $mainItems;
        } elseif (count($mainItems) == 0 && count($newBundleItems) > 0) {
            $mergedItems = $newBundleItems;
        } elseif (count($mainItems) > 0 && count($newBundleItems) > 0) {
            $mergedItems = $mainItems;
            foreach ($mergedItems as $key => $item) {
                $mergedItemQty[$item['id']] = $item['qty'];
                foreach ($newBundleItems as $bundleItem) {

                    if (!isset($mergedItems[$bundleItem['id']])) {

                        $mergedItemQty[$bundleItem['id']] = !isset($mergedItemQty[$bundleItem['id']]) ? $bundleItem['qty'] : $mergedItemQty[$bundleItem['id']];
                        //   dd($item['id'],$bundleItem['id']);
                        Log::info('$first Qty  of ' . $bundleItem['id'] . ' is ' . $mergedItemQty[$bundleItem['id']]);
                        $mergedItems[$bundleItem['id']] = ['id' => $bundleItem['id'], 'qty' => $mergedItemQty[$bundleItem['id']]];
                    } else {

                        $mergedItemQty[$bundleItem['id']] = $mergedItemQty[$item['id']] + $bundleItem['qty'];

                        unset($mergedItems[$item['id']]);
                        Log::info('$oldQty  of ' . $bundleItem['id'] . ' is ' . $mergedItemQty[$item['id']]);
                        $mergedItems[$item['id']] = ['id' => $item['id'], 'qty' => ($mergedItemQty[$item['id']])];
                    }
                }

                Log::info("____" . $key . "____");
            }
        }

        ksort($mergedItems);

        Log::info('END Original Items');
        Log::info($mergedItems);

        return $mergedItems;
    }

    public function checkItemsExistsInBundle($order, $itemsAfterUpdated, $request = null) {

        $bundles = [];
        $warehouseItems = $itemsAfterUpdated['items'];
        if ($order) { // create from App
            if ($warehouseItems['not_enough']) {
                foreach ($warehouseItems['not_enough'] as $notEnoughItem) {

                    $orderBundleItems = $order->items()->where('bundle_id', '!=', null)->get();
                    foreach ($orderBundleItems as $orderBundle) {
                        $productInBunde = $orderBundle->bundleItems()->where('product_id', $notEnoughItem['product_id'])->exists();
                        if ($productInBunde) {
                            $bundles[] = $orderBundle->bundle_id;
                        }
                    }
                }
            }
            if ($warehouseItems['out_of_stock']) {
                foreach ($warehouseItems['out_of_stock'] as $outOfStockItem) {
                    $orderBundleItems = $order->items()->where('bundle_id', '!=', null)->get();
                    foreach ($orderBundleItems as $orderBundle) {
                        $productInBunde = $orderBundle->bundleItems()->where('product_id', $outOfStockItem['product_id'])->exists();
                        if ($productInBunde) {
                            $bundles[] = $orderBundle->bundle_id;
                        }
                    }
                }
            }
        } else {
            if (isset($request) && count($request->items) > 0) { // create from Portal
                foreach ($request->items as $item) {
                    $product = Product::find($item['id']);
                    if ($product->bundle_id) {
                        $bundles[] = $product->bundle_id;
                    }
                }
            }
        }

        return array_unique($bundles);
    }

    public function buildBunldeOutOfStock($order, $itemsAfterUpdated, $request = null) {
        $outOfStockItems = [
            'out_of_stock' => []
        ];

        // check if item in out stock is in product in bundle
        // then replace this item with the parent product bundle if found
        // we push parent porduct bundle once and make sure to not repeat
        if ($order) {
            $orderBundleItems = $order->items()->where('bundle_id', '!=', null)->get();
            $orderItems = $order->items()->get();
        } else {
            if (isset($request) && count($request->items) > 0) { // create from Portal
                $orderBundleItems = collect();
                $orderItems = collect();

                foreach ($request->items as $item) {
                    $product = Product::where('id', $item['id'])->first();
                    if ($product->bundle_id) {

                        $orderBundleItems->push($product);
                    }

                    $orderItems->push($product);
                }
            }
        }

        Log::alert('buildBunldeOutOfStock ');
        Log::debug($orderBundleItems);
        Log::debug($orderItems);
        Log::alert('none');

        $warehouseItems = $itemsAfterUpdated['items'];

        if ($warehouseItems['out_of_stock']) {

            foreach ($warehouseItems['out_of_stock'] as $outOfStockItem) {

                foreach ($orderItems as $item) {
                    $itemOutStockCollection = collect($outOfStockItems['out_of_stock']);

                    // if product out of stock item in order item
                    $productId = isset($item->product_id) ? $item->product_id : $item->id; // prodcut id from order items or product id from product
                    if ($productId == $outOfStockItem['product_id']) {

                        $filtered = $itemOutStockCollection->where('product_id', $outOfStockItem['product_id']);
                        // prevent dublication of items if found
                        if (count($filtered) == 0) {
                            $outOfStockItems['out_of_stock'][] = ['product_id' => $outOfStockItem['product_id']];
                        }
                    } else {
                        // if product out of stock item NOT in order item
                        // then we check if order item is bundle

                        if ($item->bundle_id) {

                            $productInBunde = $item->bundleItems()->where('product_id', $outOfStockItem['product_id'])->first();
                            if ($productInBunde) {
                                $filtered = $itemOutStockCollection->where('product_id', $productId);
                                // prevent dublication of items if found
                                if (count($filtered) == 0) {
                                    $outOfStockItems['out_of_stock'][] = ['product_id' => $productId];
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($outOfStockItems['out_of_stock'] as $k => $item) {
            // $orderItem = $orderItems->where('id', $item['product_id'])->first();
            if ($order) {
                $orderItem = $order->items()->where('product_id', $item['product_id'])->first();
            } else {
                if (isset($request) && count($request->items) > 0) { // create from Portal
                    $orderItem = $orderItems->where('id', $item['product_id'])->first();
                }
            }

            // remove item if not found
            if (!$orderItem) {
                unset($outOfStockItems['out_of_stock'][$k]);
            }
        }

        Log::info(['buildBunldeOutOfStock' => $outOfStockItems]);
        return $outOfStockItems;
    }

    // clean all items belongs to product of type bundle in the out of stock
    public function cleanBunldeNotEnough($outOfStockItems, $order, $itemsAfterUpdated, $request = null) {
        $newItemsAfterUpdated = array();
        $allItems = [
            'not_enough' => [],
            'out_of_stock' => []
        ];

        $itemsAfter['not_enough'] = $itemsAfterUpdated['items']['not_enough'];
        Log::info([' $itemsAfter not_enough: ' => $itemsAfter['not_enough']]);
        // loop through not enough
        // compare items in not enough with items in out of stock
        //  if found then unset from item not enough
        $outOfStockItemsCollection = collect($outOfStockItems);
        foreach ($outOfStockItemsCollection->flatten() as $product) {

            //Log::info(['$outOfStock' => $product]);
            if ($order) {
                $orderBundleItem = $order->items()
                                ->where('product_id', $product)
                                ->where('bundle_id', '!=', null)->first();
            } else {
                if (isset($request) && count($request->items) > 0) { // create from Portal
                    $orderBundleItem = Product::where('id', $product)->where('bundle_id', '!=', null)->first();
                }
            }


            if ($orderBundleItem) {
                if (count($orderBundleItem->bundleItems) > 0) {
                    foreach ($orderBundleItem->bundleItems as $bunldeItem) {
                        foreach ($itemsAfter['not_enough'] as $k => $itemNotEnough) {
                            if ($bunldeItem['product_id'] == $itemNotEnough['product_id']) {
                                if ($order) {
                                    $orderItem = $order->items()->where('product_id', $itemNotEnough['product_id'])->where('bundle_id', null)->first();
                                } else {
                                    if (isset($request) && count($request->items) > 0) { // create from Portal
                                        $orderItem = Product::where('id', $itemNotEnough['product_id'])->where('bundle_id', null)->first();
                                    }
                                }

                                if (!$orderItem) {
                                    unset($itemsAfter['not_enough'][$k]);
                                }
                            }
                        }
                    }
                }
            }
        }
        Log::info([' $itemsAfter not_enough: ' => $itemsAfter['not_enough']]);
        $allItems = [
            'not_enough' => $itemsAfter['not_enough'], //$itemsAfterUpdated['items']['not_enough'],
            'out_of_stock' => $outOfStockItems['out_of_stock']
        ];

        $newItemsAfterUpdated = [
            'warehouse_id' => $itemsAfterUpdated['warehouse_id'],
            'items' => $allItems
        ];
        Log::info(['cleanBunldeNotEnough' => $newItemsAfterUpdated]);
        return $newItemsAfterUpdated;
    }

    public function reBuildItemsAfterUpdatedIfMutltiBundle($order, $itemsAfterUpdated, $request) {
        if (!$itemsAfterUpdated['items']['not_enough']) {
            return $itemsAfterUpdated;
        }

        $newItemsAfterUpdated = array();
        $allItems = [
            'not_enough' => [],
            'out_of_stock' => $itemsAfterUpdated['items']['out_of_stock']
        ];

        $bundleQtyAvialable = []; // perpare array to save all item qty available in bundle to be accepted
        $warehouseItems = $itemsAfterUpdated['items'];
        //  dd($allItems,$warehouseItems);
        //==
        if ($order) {
            $orderItems = $order->items()->get();
            $bundleItems = $order->items()->where('bundle_id', '!=', null)->get();
        } else {
            if (isset($request) && count($request->items) > 0) { // create from Portal
                $bundleItems = collect();
                $orderItems = collect();

                foreach ($request->items as $item) {
                    $product = Product::where('id', $item['id'])->first();
                    $product->qty_shipped = $item['qty'];

                    if ($product->bundle_id) {

                        $bundleItems->push($product);
                    }

                    $orderItems->push($product);
                }
            }
        }

        // get all items not enough
        // calculate the $maxAllowedBundle
        if ($warehouseItems['not_enough']) {
            $explodedProducts = [];
            foreach ($bundleItems as $item) {
                // if product out of stock item in order item
                $productId = isset($item->product_id) ? $item->product_id : $item->id; // prodcut id from order items or product id from product

                if (!in_array($productId, array_column($warehouseItems['out_of_stock'], 'product_id'))) {
                    foreach ($item->bundleItems as $bundleItem) {
                        if (in_array($bundleItem['product_id'], array_column($warehouseItems['not_enough'], 'product_id'))) {
                            if (isset($explodedProducts[$productId])) {
                                $arrayedProducts = $explodedProducts[$productId]['items'];
                                array_push($arrayedProducts, ['product_id' => $bundleItem['product_id'], 'quantity' => $bundleItem['quantity']]);
                                $new_item = ['quantity' => $item['qty_shipped'], 'items' => $arrayedProducts];
                            } else {
                                $new_item = ['quantity' => $item['qty_shipped'], 'items' => [['product_id' => $bundleItem['product_id'], 'quantity' => $bundleItem['quantity']]]];
                            }
                            $explodedProducts[$productId] = $new_item;
                        }
                    }
                }
            }
            Log::info($explodedProducts);
            //dd($explodedProducts);
            $this->notEnoughToOrder = $warehouseItems['not_enough'];
            $this->outOfStockToOrder = $warehouseItems['out_of_stock'];
            foreach ($explodedProducts as $key => $productedBundle) {
                $requested = $productedBundle['quantity'];
                $available = 0;

                Log::info('$requested ' . $requested . ' of ' . $key);
                for ($i = 1; $i <= $requested; $i++) {
                    $qtyExist = true;
                    foreach ($productedBundle['items'] as $item) {
                        foreach ($this->notEnoughToOrder as $notEn) {
                            if ($notEn['product_id'] == $item['product_id']) {
                                Log::info('product_id  ' . $item['product_id']);
                                Log::info('quantity  ' . $item['quantity'] . '  - not:' . $notEn['available_qty']);
                                if ($item['quantity'] > $notEn['available_qty']) {
                                    $qtyExist = false;
                                    break;
                                }
                            }
                        }
                    }
                    if ($qtyExist) {
                        $newNotEnough = [];
                        foreach ($this->notEnoughToOrder as $notEn) {
                            foreach ($productedBundle['items'] as $item) {
                                if ($notEn['product_id'] == $item['product_id']) {
                                    $notEn['available_qty'] = $notEn['available_qty'] - $item['quantity'];
                                }
                            }
                            array_push($newNotEnough, $notEn);
                        }
                        $this->notEnoughToOrder = $newNotEnough;
                        $available++;
                    }
                }
                Log::info('available ' . $available);
                //  if available = 0 then bundle is out of stock
                if ($available == 0) {
                    $this->outOfStockToOrder[] = ['product_id' => $key];
                }

                // if available <  $requested  bundle is not enf (with available value)
                if ($available > 0 && ($available < $requested)) {
                    $this->notEnoughToOrder[] = ['product_id' => $key, 'available_qty' => $available];
                }

                // if available ==   $requested do no thing
                if ($available == $requested) {
                    
                }
            }
            Log::info('before');
            Log::info($this->notEnoughToOrder);
            Log::info($this->outOfStockToOrder);

            $itemNotEnoughCollection = collect($this->notEnoughToOrder);
            $itemOutStockCollection = collect($this->outOfStockToOrder);
            foreach ($this->notEnoughToOrder as $k => $notEnf) {
                foreach ($orderItems as $item) {
                    // if product out of stock item in order item
                    $productId = isset($item->product_id) ? $item->product_id : $item->id; // prodcut id from order items or product id from product
                    // check if there is items in not enf also found in the main order request
                    // then add the not enf of items
                    if (!$item['bundle_id'] && ($notEnf['product_id'] == $productId)) {

                        if ($notEnf['available_qty'] > 0) {
                            if ($notEnf['available_qty'] < $item['qty_shipped']) {
                                $filtered = $itemNotEnoughCollection->where('product_id', $productId);
                                if (count($filtered) == 0) {
                                    $this->notEnoughToOrder[] = ['product_id' => $notEnf['product_id'], 'available_qty' => $notEnf['available_qty']];
                                } else {
                                    unset($this->notEnoughToOrder[$k]);
                                    $this->notEnoughToOrder[] = ['product_id' => $notEnf['product_id'], 'available_qty' => $notEnf['available_qty']];
                                }
                            } else {
                                unset($this->notEnoughToOrder[$k]);
                            }
                        }

                        // out of stock
                        if ($notEnf['available_qty'] == 0) {
                            unset($this->notEnoughToOrder[$k]);
                            $this->outOfStockToOrder[] = ['product_id' => $notEnf['product_id']];
                        }
                    }
                    // if items in new not enf  and not in oreder request then remove from items not enf
                    if ($order) {
                        $itemBunlde = $order->items()
                                        ->where('bundle_id', '!=', null)
                                        ->where('product_id', $notEnf['product_id'])->first();
                    } else {
                        if (isset($request) && count($request->items) > 0) { // create from Portal
                            $itemBunlde = Product::where('id', $notEnf['product_id'])->where('bundle_id', '!=', null)->first();
                        }
                    }

                    if (!$itemBunlde && ($notEnf['product_id'] != $productId)) {
                        unset($this->notEnoughToOrder[$k]);
                    }
                }
            }

            // fix array index by rearrange
            $this->notEnoughToOrder = array_values(array_filter($this->notEnoughToOrder));
            $this->outOfStockToOrder = array_values(array_filter($this->outOfStockToOrder));
            $allItems = [
                'not_enough' => $this->notEnoughToOrder,
                'out_of_stock' => $this->outOfStockToOrder
            ];
        }
        $newItemsAfterUpdated = [
            'warehouse_id' => $itemsAfterUpdated['warehouse_id'],
            'items' => $allItems
        ];
        Log::info(['before   not_enough $newItemsAfterUpdated: ' => $newItemsAfterUpdated]);

        return $newItemsAfterUpdated;
    }

    public function mergedItemsFromCollection(Collection $items) {
        $data['bundle_items'] = [];

        $mergedItems = [];
        $mainItems = [];
        $newBundleItems = [];

        foreach ($items as $item) {

            $product = Product::find($item->product_id);

            if ($product->bundle_id) {
                $bundleItems = $product->bundle->items;
                foreach ($bundleItems as $bundleItem) {
                    $data['bundle_items'][$product->id][] = ['id' => $bundleItem['product_id'], 'qty' => $bundleItem['quantity'] * $item->qty_shipped];
                }
            } else {
                $mainItems[$product->id] = ['id' => $item->product_id, 'qty' => $item->qty_shipped];
            }
        }

        $itemBundleQty[] = 0;

        foreach ($data['bundle_items'] as $key => $items) {

            foreach ($items as $item) {
                if (!isset($newBundleItems[$item['id']])) {

                    $itemBundleQty[$item['id']] = !isset($itemBundleQty[$item['id']]) ? $item['qty'] : $itemBundleQty[$item['id']];
                    $newBundleItems[$item['id']] = ['id' => $item['id'], 'qty' => $itemBundleQty[$item['id']]];
                } else {
                    $itemBundleQty[$item['id']] = $itemBundleQty[$item['id']] + $item['qty'];
                    unset($newBundleItems[$item['id']]);
                    $newBundleItems[$item['id']] = ['id' => $item['id'], 'qty' => ($itemBundleQty[$item['id']])];
                }
            }
        }

        $mergedItemQty[] = 0;

        if (count($mainItems) > 0 && count($newBundleItems) == 0) {
            $mergedItems = $mainItems;
        } elseif (count($mainItems) == 0 && count($newBundleItems) > 0) {
            $mergedItems = $newBundleItems;
        } elseif (count($mainItems) > 0 && count($newBundleItems) > 0) {
            $mergedItems = $mainItems;
            foreach ($mergedItems as $key => $item) {
                $mergedItemQty[$item['id']] = $item['qty'];
                foreach ($newBundleItems as $bundleItem) {

                    if (!isset($mergedItems[$bundleItem['id']])) {

                        $mergedItemQty[$bundleItem['id']] = !isset($mergedItemQty[$bundleItem['id']]) ? $bundleItem['qty'] : $mergedItemQty[$bundleItem['id']];
                        $mergedItems[$bundleItem['id']] = ['id' => $bundleItem['id'], 'qty' => $mergedItemQty[$bundleItem['id']]];
                    } else {

                        $mergedItemQty[$bundleItem['id']] = $mergedItemQty[$item['id']] + $bundleItem['qty'];
                        unset($mergedItems[$item['id']]);
                        $mergedItems[$item['id']] = ['id' => $item['id'], 'qty' => ($mergedItemQty[$item['id']])];
                    }
                }
            }
        }
        ksort($mergedItems);

        return $mergedItems;
    }

    public function updateBundleProductsStockInAreaAndWarehouse($order) {

        Event::dispatch('app.order.update_bundle_products_stock_in_area_and_warehouse', $order);
        logOrderActionsInCache($order->id, 'update_bundle_products_stock_in_area_and_warehouse');
        // get all item for this order
        $orderItems = $order->items()->get();
        // get all old item in order and merge it with order items if old items exist
        if ($order->oldItems()->count() > 0) {
            $orderItemsOld = $order->oldItems()->get();
            $orderItems = $orderItems->merge($orderItemsOld);
            $orderItems = $orderItems->unique('product_id');
        }
        // merge items
        $mergedItems = collect($this->mergedItemsFromCollection($orderItems));
        // get items of merged items
        $itemIDs = $mergedItems->pluck('id')->toArray();

        // get all bundle that contains those items
        $bundles = Bundle::whereHas('areas', function ($query) use ($order) {
                    $query->where('area_id', $order->area_id);
                })->whereHas('items', function ($query) use ($itemIDs) {
                    $query->whereIn('product_id', $itemIDs);
                })->active()->get();

        // find product ot type bundle
        $productBundles = Product::whereIn('bundle_id', $bundles->pluck('id')->toArray())->get();
        Log::info('update_bundle_products_stock_in_area_and_warehouse count: ' . $productBundles->count());
        foreach ($productBundles as $product) {
            $productBundleItems = $product->bundleItems;
            Log::info(' bundle_ product_id of  :'.$product->id .' is =>' . $productBundleItems->pluck('product_id'));
            // check qty stock in stock for product that is bundle
            $qtyInStock = [];
            foreach ($productBundleItems as $item) { // item in product bundle
                $invAreay = InventoryArea::where(['product_id' => $item['product_id'], 'area_id' => $order->area_id])->first();
                if ($invAreay) {
                    $invQty = $invAreay->total_qty;
                    $bundleQty = $item->quantity;
                    $qty = $invQty > 0 ? $invQty / $bundleQty : 0; // 15 / 4 = 3.75 = 3

                    array_push($qtyInStock, intval($qty));
                } else {
                    array_push($qtyInStock, 0);
                }
            }

            $totalInStock = min($qtyInStock);
           
            if ($totalInStock < 1) {
                // Set total_qty = 0 in area
                $productInInventoryArea = InventoryArea::where('product_id', $product['id'])->where('area_id', $order->area_id)->first();
                if ($productInInventoryArea) {
                    $productInInventoryArea->total_qty = 0;
                    $productInInventoryArea->save();
                }

                // Set qty = 0 in warehouse
                $productInInventoryWarehouse = InventoryWarehouse::where('product_id', $product['id'])->where('warehouse_id', $order->warehouse_id)->where('area_id', $order->area_id)->first();
                if ($productInInventoryWarehouse) {
                    $productInInventoryWarehouse->qty = 0;
                    $productInInventoryWarehouse->save();
                }
            } else {
                // Set total_qty = 1 in area
                $productInInventoryArea = InventoryArea::where('product_id', $product['id'])->where('area_id', $order->area_id)->first();
                if ($productInInventoryArea) {
                    $productInInventoryArea->total_qty = 1;
                    $productInInventoryArea->save();
                }

                // Set qty = 1 in warehouse
                $productInInventoryWarehouse = InventoryWarehouse::where('product_id', $product['id'])->where('warehouse_id', $order->warehouse_id)->where('area_id', $order->area_id)->first();
                if ($productInInventoryWarehouse) {
                    $productInInventoryWarehouse->qty = 1;
                    $productInInventoryWarehouse->save();
                }
            }
        }
    }

}
