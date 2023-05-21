<?php

namespace Webkul\Sales\Listeners;

use Webkul\Core\Models\Sold;
use Webkul\User\Models\Admin;
use Webkul\Sales\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\Core\Services\SendPushNotification;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Core\Services\SendNotificationUsingFCM;

class OrderAction implements ShouldQueue {

    /**
     * OrderRepository object
     *
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * OrderLogs constructor.
     * @param OrderRepository $orderRepository
     */
    public function __construct(OrderRepository $orderRepository) {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Handle the event.
     *
     * @param Order $order
     * @param string $logType
     * @return void
     */
    public function updateProductSoldCount(Order $order) {
        foreach ($order->items as $item) {
            // update Product Sold Count
            $product = $item->item;
            $product->sold_count = $product->sold_count + 1;
            $product->save();

            // for area detatils
            // if($product->solds){
            //    $areaSoldProduct= $product->solds->where('area_id',$order->area_id)->first();
            //    if($areaSoldProduct){
            //        $areaSoldProduct->sold_count += 1 ;
            //        $areaSoldProduct->save();
            //    }
            // }
            //$product->solds()->create(['area_id'=>$order->area_id]);

            // update Sub Category
            foreach ($product->subCategories as $subCategory) {
                $subCategory->sold_count = $subCategory->sold_count + 1;
                $subCategory->save();

                // for area detatils
                // if($subCategory->solds){
                //    $areaSubCategory= $subCategory->solds->where('area_id',$order->area_id)->first();
                //    if($areaSubCategory){
                //        $areaSubCategory->sold_count += 1 ;
                //        $areaSubCategory->save();
                //    }
                // }
                //$subCategory->solds()->create(['area_id'=>$order->area_id]);


                // update Category
                foreach ($subCategory->parentCategories as $category) {
                    $category->sold_count = $category->sold_count + 1;
                    $category->save();

                    // for area detatils
                    // if($category->solds){
                    //    $areaCategory= $category->solds->where('area_id',$order->area_id)->first();
                    //    if($areaCategory){
                    //        $areaCategory->sold_count += 1 ;
                    //        $areaCategory->save();
                    //    }
                    // }
                    //$category->solds()->create(['area_id'=>$order->area_id]);

                }
            }
        }
    }

    /**
     * Handle the event.
     *
     * @param Order $order
     * @param string $logType
     * @return void
     */
    public function orderStatusChanged(Order $order) {
        // $admins = Admin::whereHas('deviceToken')->with('deviceToken')->get();

        // $tokens = [];
        // foreach ($admins as $admin) {
        //     $tokens = array_merge($tokens, $admin->deviceToken->pluck('token')->toArray());
        // }

        // $data = [
        //     'title' => 'Order Status Changed',
        //     'body' => 'Order Status Changed'
        // ];

        // return (new SendNotificationUsingFCM())->sendNotification($tokens, $data);
    }

}
