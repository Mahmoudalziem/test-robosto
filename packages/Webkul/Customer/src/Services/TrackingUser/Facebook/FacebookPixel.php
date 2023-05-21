<?php

namespace Webkul\Customer\Services\TrackingUser\Facebook;

use Webkul\Sales\Models\Order;
use App\Enums\TrackingUserEvents;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Services\TrackingUser\TrackingType;

class FacebookPixel extends FacebookConfig implements TrackingType
{
    /**
     * @var Customer
     */
    public $customer;

    /**
     * @var string
     */
    private $eventName;

    /**
     * @var array
     */
    private $data;


    public function __construct(string $eventName, Customer $customer, array $data = null)
    {
        parent::__construct();

        $this->eventName = $eventName;
        $this->customer = $customer;
        $this->data = $data;
    }

    /**
     * Send The Action Request To facebook pixel
     * 
     * @return void
     */
    public function submitTheAction()
    {
        $data = $this->handleEventType();
        $payload = json_encode($data['data']);
        // dd($data);
        $url = "{$this->baseURL()}?data=[{$payload}]&access_token={$data['access_token']}";
        $res = requestWithCurl($url, 'POST');
        
        Log::notice(["Send Event Response"  => $res]);
    }

    private function handleEventType()
    {
        $data = $this->prepareData($this->eventName);
        
        switch ($this->eventName) {
            case TrackingUserEvents::INITIATE_CHECKOUT:
                return $this->appendCheckoutCustomData($data);
                break;

            case TrackingUserEvents::ADD_TO_CART:
                return $this->appendItemCustomData($data);
                break;

            case TrackingUserEvents::SEARCH:
                return $this->appendSearchCustomData($data);
                break;

            case TrackingUserEvents::COMPLETE_REGISTRATION:
                return $this->appendRegisterationCustomData($data);
                break;

            case TrackingUserEvents::PURCHASE:
                return $this->appendPurchaseCustomData($data);
                break;

            case TrackingUserEvents::ADD_PAYMENT_INFO:
                return $data;
                break;

            default:
                return $data;
                break;
        }
    }

    /**
     * @return array
     */
    private function prepareData($eventName)
    {
        return [
            'data' => [
                "event_name"    => $eventName,
                "event_time"    => time(),
                "user_data"     =>  [
                    "fn"    =>  hash('sha256', $this->customer->name),
                    "em"    =>  [
                        hash('sha256', $this->customer->email)
                    ],
                    "ph"    =>  [
                        hash('sha256', $this->customer->phone)
                    ],
                    "client_ip_address" => $this->data && isset($this->data['server']) ? $this->data['server']['REMOTE_ADDR'] : null,
                    "client_user_agent" => $this->data && isset($this->data['server']) ? $this->data['server']['HTTP_USER_AGENT'] : null,
                ],
                "action_source" => "website"
            ],
            'access_token'  => $this->getFbPixelToken()
        ];
    }

    private function appendCheckoutCustomData($data)
    {
        if ($this->data == null || !isset($this->data['request_data'])) {
            return $data;
        }

        $items = $this->data['request_data']['items'];
        
        $data['data']['custom_data'] = [
            'num_items'  => count($items),
            'content_ids'   =>  array_column($items, 'id'),
            'currency'  =>  'EGP',
        ];

        foreach ($items as $item) {
            $product = Product::findOrFail($item['id']);
            $data['data']['custom_data']['contents'][] = [
                'id' => $product->id,
                'quantity' => $item['qty'],
                'item_price' => (float) $product->price,
                'delivery_category' =>  'home_delivery'
            ];
        }
        
        return $data;
    }

    private function appendItemCustomData($data)
    {
        if (!isset($this->data['item'])) {
            return $data;
        }

        $product = Product::findOrFail($this->data['item']['id']);
        $data['data']['event_id'] = 'event.id.' . time() . $product->id;
        $data['data']['custom_data'] = [
            'content_category'  => str_replace(" ", "-", $product->subCategories->first()->name),
            'content_name'  => $product->name,
            'content_type'  =>  'product',
            'currency'  =>  'EGP',
            'contents'  =>  [[
                'id' => $product->id,
                'quantity' => 1,
                'item_price' => (float) $product->price,
            ]]
        ];
        return $data;
    }

    private function appendSearchCustomData($data)
    {
        if ($this->data == null || !isset($this->data['request_data'])) {
            return $data;
        }

        $data['data']['custom_data'] = [
            'search_string'  => str_replace(" ", "-", $this->data['request_data']['query']),
        ];
        
        return $data;
    }

    private function appendRegisterationCustomData($data)
    {
        $data['data']['custom_data'] = [
            'status'  => 'registered'
        ];

        return $data;
    }

    private function appendPurchaseCustomData($data)
    {
        if ($this->data == null || !isset($this->data['order_id'])) {
            return $data;
        }

        $order = Order::find($this->data['order_id']);
        $items = $order->items;
        $data['data']['custom_data'] = [
            'currency'  =>  'EGP',
            'order_id'  =>  "{$order->increment_id}",
            'value'     =>  (float) $order->final_total,
            'num_items'  => count($items),
            'content_ids'   =>  $items->pluck('product_id')->toArray(),
            'delivery_category'     =>  'home_delivery',
        ];

        foreach ($items as $item) {
            $data['data']['custom_data']['contents'][] = [
                'id' => $item->product_id,
                'quantity' => $item->qty_ordered,
                'item_price' => (float) $item->price
            ];
        }
        
        return $data;
    }


    private function appendAllItemsCustomDataWithOneEvent($data)
    {
        $basicData = $data['data'];
        $data['data'] = null;

        foreach ($this->data['request_data']['items'] as $key => $item) {
            $product = Product::findOrFail($item['id']);
            $basicData['event_id'] = "event.id.{$item['id']}";
            $basicData['custom_data'] = [
                'content_category'  => $product->subCategories->first()->name,
                'content_name'  => $product->name,
                'content_type'  =>  'product',
                'currency'  =>  'EGP',
                'contents'  =>  [[
                    'id' => $product->id,
                    'quantity' => 1,
                    'item_price' => $product->price,
                ]]
            ];
            $data['data'][$key] = $basicData;
        }
        return $data;
    }
}
