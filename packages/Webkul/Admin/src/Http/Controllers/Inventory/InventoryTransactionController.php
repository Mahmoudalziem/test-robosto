<?php

namespace Webkul\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use Webkul\User\Models\Role;
use Webkul\Sales\Models\Order;
use function DeepCopy\deep_copy;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\Event;
use Webkul\Collector\Models\Collector;
use Webkul\Inventory\Models\Warehouse;
use App\Exceptions\ResponseErrorException;
use Webkul\Inventory\Models\InventoryProduct;
use Webkul\Core\Services\SendPushNotification;
use Webkul\Inventory\Models\InventoryTransaction;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Http\Resources\Inventory\InventoryTransactionAll;
use Webkul\Admin\Http\Requests\Inventory\InventoryTransactionRequest;
use Webkul\Admin\Http\Resources\Inventory\InventoryTransactionSingle;
use Webkul\Admin\Repositories\Inventory\InventoryTranasctionRepository;
use Webkul\Inventory\Models\InventoryTransactionProduct;
use Webkul\Admin\Http\Resources\Product\ProductAll;

class InventoryTransactionController extends BackendBaseController {

    protected $inventoryTransactionRepository;

    public function __construct(InventoryTranasctionRepository $inventoryTransactionRepository) {
        $this->inventoryTransactionRepository = $inventoryTransactionRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request) {
        $inventoryTransactionRepository = $this->inventoryTransactionRepository->list($request);
        $data = new InventoryTransactionAll($inventoryTransactionRepository); // using InventoryTransacttion repository
        return $this->responsePaginatedSuccess($data, null, $request);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(InventoryTransactionRequest $request) {
        $request = $request->only('from_warehouse_id', 'to_warehouse_id', 'products');
        $request['admin_id'] = auth('admin')->user()->id;
        $inventoryTransaction = $this->inventoryTransactionRepository->create($request);

        Event::dispatch('admin.inventoryTransaction.created', $inventoryTransaction);
        Event::dispatch('admin.log.activity', ['create', 'inventoryTransaction', $inventoryTransaction, auth('admin')->user(), $inventoryTransaction]);

        // send notification to operation manager
        $payload['model'] = $inventoryTransaction;
        Event::dispatch('admin.alert.admin_create_transfer_order', [auth('admin')->user(), $payload]);

        $this->sendNotificationToCollectorFromWarehouse($inventoryTransaction);
        $this->sendNotificationToCollectorInWarehouse($inventoryTransaction);
        return $this->responseSuccess($inventoryTransaction);
    }

    public function searchProduct(Request $request) {
        $request = $request->only('key', 'from_warehouse_id', 'to_warehouse_id');
        $inventoryTransactions = $this->inventoryTransactionRepository->searchProduct($request);
        $data = new ProductAll($inventoryTransactions);
        Event::dispatch('admin.inventoryTransaction.created', $inventoryTransactions);
        return $this->responsePaginatedSuccess($data, null, $request);
    }

    public function selectProduct(Product $product, Request $request) {
        $request = $request->only('from_warehouse_id', 'to_warehouse_id');
        $request['product'] = $product;

        // firs Check if this product Exist in [ Pending Order ]
        $warehouse = Warehouse::findOrFail($request['from_warehouse_id']);
        $area = $warehouse->area_id;

        $pendingOrderItems = Order::where('area_id', $area)
                        ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_WAITING_CUSTOMER_RESPONSE])
                        ->with('items')->get()
                        ->pluck('items')->flatten();

        if ($pendingOrderItems->where('product_id', $product->id)->first()) {
            return $this->responseError(422, 'This item Exist in Pending Order');
        }

        $inventoryTransactions = $this->inventoryTransactionRepository->selectProduct($request);
        Event::dispatch('admin.inventoryTransaction.created', $inventoryTransactions);
        return $this->responseSuccess($inventoryTransactions);
    }

    public function showProductSku($sku, Request $request) {
        $request = $request->only('from_warehouse_id');
        $request['sku'] = $sku;
        $inventoryTransactions = $this->inventoryTransactionRepository->showProductSku($sku, $request);
        Event::dispatch('admin.inventoryTransaction.created', $inventoryTransactions);
        return $this->responseSuccess($inventoryTransactions);
    }

    public function deleteProduct($id) {
        $inventoryTransactionProduct = InventoryTransactionProduct::findOrFail($id);
        $inventoryTransaction = InventoryTransaction::find($inventoryTransactionProduct->inventory_transaction_id);

        if ($inventoryTransaction->status == InventoryTransaction::STATUS_CANCELLED || $inventoryTransaction->status == InventoryTransaction::STATUS_TRANSFERRED  ) {
            return $this->responseError(422,"You can not remove item from this transfer !");
        }
        
        if($inventoryTransaction->transactionProducts->count() == 1){
              return $this->responseError(422,"You can only cancel the whole transfer !");
        }

        $inventoryTransactions = $this->inventoryTransactionRepository->deleteProduct($inventoryTransaction, $inventoryTransactionProduct);
        
        return $this->responseSuccess($inventoryTransactions);
    }

    /**
     * Show the specified supplier.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile($id) {
        $inventoryTransactions = $this->inventoryTransactionRepository->findOrFail($id);
        $data = new InventoryTransactionSingle($inventoryTransactions);
        Event::dispatch('admin.inventoryTransaction.show', $inventoryTransactions);
        return $this->responseSuccess($data);
    }

    public function setStatus($id, Request $request) {
        $inventoryTransaction = $this->inventoryTransactionRepository->findOrFail($id);
        $before = deep_copy($inventoryTransaction);

        $request = $request->only('status');
        $request['admin_id'] = auth('admin')->user()->id;
        $request['inventoryTransactions'] = $inventoryTransaction;

        // Check Transaction Status
        $this->validateTransactionStatus($inventoryTransaction, $request['status']);

        $inventoryTransaction = $this->inventoryTransactionRepository->setStatus($request);

        Event::dispatch('admin.inventoryTransaction.statusChanged', $inventoryTransaction);
        Event::dispatch('admin.log.activity', ['update-status', 'inventoryTransaction', $inventoryTransaction, auth('admin')->user(), $inventoryTransaction, $before]);

        return $this->responseSuccess($inventoryTransaction);
    }

    /**
     * @param InventoryTransaction $inventoryTransaction
     * @param string $status
     * 
     * @return void
     */
    private function validateTransactionStatus(InventoryTransaction $inventoryTransaction, string $status) {
        if (in_array($inventoryTransaction->status, [InventoryTransaction::STATUS_CANCELLED, InventoryTransaction::STATUS_TRANSFERRED])) {
            throw new ResponseErrorException(406, 'عذراً لقد تم الانتهاء من هذا الطلب من قبل');
        }
    }


    public function sendNotificationToCollectorFromWarehouse($inventoryTransactions)
    {
        $coleclorOut =Collector::where('warehouse_id',$inventoryTransactions->fromWarehouse->id)->first();
        $tokensOut = $coleclorOut?$coleclorOut->deviceToken->pluck('token')->toArray():null;
        if($tokensOut){
            $data = [
                'title' => 'Inventory Transactions out',
                'body' => 'transfer_out',
                'data' => [
                    'inventoryTransactions_id' => $inventoryTransactions->id,
                    'key' => 'transfer'
                ]
            ];
            return SendPushNotification::send($tokensOut, $data);
        }
    }

    public function sendNotificationToCollectorInWarehouse($inventoryTransactions) {

        $coleclorIn = Collector::where('warehouse_id', $inventoryTransactions->toWarehouse->id)->first();
        $tokensIn = $coleclorIn ? $coleclorIn->deviceToken->pluck('token')->toArray() : null;
        if ($tokensIn) {
            $data = [
                'title' => 'Inventory Transactions in',
                'body' => 'transfer_in',
                'data' => [
                    'inventoryTransactions_id' => $inventoryTransactions->id,
                    'key' => 'transfer'
                ]
            ];
            return SendPushNotification::send($tokensIn, $data);
        }
    }

    protected function responseCustomPaginatedSuccess($data, $message = null, $request) {
        $data->status = 200;
        $data->success = true;
        $data->message = $message;

        return response()->json($data);
    }

}
