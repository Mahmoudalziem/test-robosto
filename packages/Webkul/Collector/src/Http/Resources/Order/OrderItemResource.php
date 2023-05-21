<?php

namespace Webkul\Collector\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{

    protected $append;
    private static $data;

    public function __construct($resource, $append = null)
    {


        $this->append = $append;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return array
     */
    public function toArray($request)
    {



        //        if ($this->bundle_id) {
        //            $skus = [
        //                [
        //                    'sku' => 'sku',
        //                    'qty' => $this->qty_shipped
        //                ]
        //            ];
        //        } else {
        //            $skus = new OrderItemSkuResource($this->skus);
        //            if ($this->skus->isEmpty()) {
        //                $skus = [
        //                    [
        //                        'sku' => 'sku',
        //                        'qty' => $this->qty_shipped
        //                    ]
        //                ];
        //            }
        //        }

        $append = self::$data;
        $product_id = $this->product_id;
        // Using keys
        $filteredSkus = array_filter($append['orderItemSkus'], function ($k) use ($product_id) {
            return $k == $product_id;
        }, ARRAY_FILTER_USE_KEY);
        $filteredSkus = array_values($filteredSkus)[0] ?? null;


        if ($filteredSkus) {
            if (count($filteredSkus) == 0 || $this->bundle_id) {
                $filteredSkus = [
                    [
                        'barcode' => $this->item->barcode ? substr($this->item->barcode, -4) : null,
                        'sku' => $this->item->barcode,//'sku',
                        'qty' => $this->qty_shipped
                    ]
                ];
            }
        } else {

            $filteredSkus = [
                [
                    'barcode' => $this->item->barcode ? substr($this->item->barcode, -4) : null,
                    'sku' => $this->item->barcode,//'sku',
                    'qty' => $this->qty_shipped
                ]
            ];
        }
        if(isset($append["shippiment_tracking_number"])){
            $this->item->name = "شحن - ".$append["shippiment_tracking_number"];
        }
        return [
            'product_id' => $this->product_id,
            'order_product_id' => $this->id,
            'product_name' => $this->item->name,
            'product_barcode' => $this->item->barcode,
            'unit' => $this->item->unit_value . ' ' . $this->item->unit->name,
            'product_weight' => $this->item->weight,
            'barcode' => $this->item->barcode,
            'image' => $this->item->image_url,
            'qty_shipped' => $this->qty_shipped,
            'price' => (float) number_format($this->item->price, 2),
            'position' => $this->shelve_position,
            'shelf' => $this->shelve_name,
            //'skus' => $skus,
            'skus' => $filteredSkus,
        ];
    }

    //I made custom function that returns collection type
    public static function customCollection($resource, $data): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        //you can add as many params as you want.
        self::$data = $data;
        return parent::collection($resource);
    }
}