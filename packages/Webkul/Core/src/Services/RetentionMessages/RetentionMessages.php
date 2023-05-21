<?php

namespace Webkul\Core\Services\RetentionMessages;

use App\Jobs\RetentionsNotifier;
use Webkul\Core\Models\Notifier;
use Illuminate\Support\Facades\DB;
use Webkul\Customer\Models\Customer;
use App\Jobs\CustomersRetentionMessages;
use Webkul\Core\Models\RetentionMessage;
use Webkul\Core\Models\RetentionCustomer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class RetentionMessages
{

    /**
     * @return bool
     */
    public function dispatchRetention()
    {
        $retentions = RetentionMessage::active()->get();
        $nonUsedCustomers = array_unique($this->getNonUsedCustomers());
        
        foreach ($retentions as $retention) {
            $days = now()->subDays($retention->no_of_days)->format('Y-m-d h:i:s');
            $orders  = $retention->no_of_orders ?? 0;

            $stm = $orders > 0 ? $this->ordersDateStatment($days, $orders) : $this->customersJoinedDateStatment($days, $orders);
            // Convert Result to Collection
            $result = collect(DB::select(DB::raw($stm)));
            if ($result->isEmpty()) {
                continue;
            }

            $customerIds = $this->handleCustomers($result, $retention, $nonUsedCustomers);

            if (count($customerIds) == 0) {
                continue;
            }

            // Save Customers who will be send notification
            saveNotifiers($customerIds, Notifier::RETENTION_TYPE, $retention->id);

            // Fetch Customers by there Ids
            $customers = Customer::whereIn('id', $customerIds)->get();

            // Save retentioned Customers
            $this->saveRetentionedCustomers($customers, $retention);

            // Fire the job that will be send sms
            RetentionsNotifier::dispatch($retention);

        }
    }

    /**
     * @param mixed $days
     * @param int $orders
     * 
     * @return string
     */
    private function ordersDateStatment($days, int $orders)
    {
        return " SELECT OT.customer_id , OT.order_count , OD.latest_order  FROM  
                            ( SELECT customer_id , count(id) AS 'order_count' FROM orders WHERE status = 'delivered' GROUP BY customer_id) OT
                    INNER JOIN
                            (SELECT customer_id , MAX(created_at) AS 'latest_order' FROM orders WHERE status = 'delivered'  GROUP BY customer_id) OD
                            ON OT.customer_id = OD.customer_id
                    WHERE OT.order_count > {$orders} AND OD.latest_order = '{$days}' ;
            ";
    }

    /**
     * @param mixed $days
     * @param int $orders
     * 
     * @return string
     */
    private function customersJoinedDateStatment($days, int $orders)
    {
        return "SELECT id AS customer_id, total_orders, delivered_orders, created_at FROM customers WHERE delivered_orders = 0 AND created_at <= '{$days}' ";
    }

    /**
     * @param SupportCollection $result
     * @param RetentionMessage $retention
     * @param array $nonUsedCustomers
     * 
     * @return array
     */
    private function handleCustomers(SupportCollection $result, RetentionMessage $retention, array $nonUsedCustomers)
    {
        // Get Customer IDs
        $customerIds = $result->pluck('customer_id')->toArray();

        // Exclude Retentioned Customers
        $customerIds = $this->excludeRetentionedCustomers($retention, $customerIds);

        // Exclude 
        $customerIds = $this->excludeNonUsedCustomers($customerIds, $nonUsedCustomers);

        return $customerIds;
    }

    /**
     * @param RetentionMessage $retention
     * @param array $customerIds
     * 
     * @return array
     */
    private function excludeRetentionedCustomers(RetentionMessage $retention, array $customerIds)
    {
        if ($retention->retentionedCustomers->isNotEmpty()) {
            $retentionedCustomers = $retention->retentionedCustomers->pluck('customer_id')->toArray();

            return array_values(array_diff($customerIds, $retentionedCustomers));
        }
        return $customerIds;
    }
    
    /**
     * @param array $usedCustomers
     * @param array $customerIds
     * 
     * @return array
     */
    private function excludeNonUsedCustomers(array $customerIds, array $nonUsedCustomers)
    {
        return array_values(array_diff($customerIds, $nonUsedCustomers));
    }
    
    /**
     * 
     * @return array
     */
    private function getNonUsedCustomers()
    {
        return RetentionCustomer::where('used', 0)->get()->pluck('customer_id')->toArray();
    }

    /**
     * @param Collection $customers
     * @param RetentionMessage $retention
     * 
     * @return void
     */
    private function saveRetentionedCustomers(Collection $customers, RetentionMessage $retention)
    {
        $data = [];
        foreach ($customers as $customer) {
            $data[] = [
                'retention_id'   => $retention->id,
                'customer_id'   => $customer->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }
        
        DB::table('retention_customers')->insert($data);
    }
}
