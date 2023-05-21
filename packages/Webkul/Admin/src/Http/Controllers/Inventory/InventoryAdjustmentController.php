<?php

namespace Webkul\Admin\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use Webkul\User\Models\Role;
use function DeepCopy\deep_copy;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use App\Exceptions\ResponseErrorException;
use Webkul\Inventory\Models\InventoryAdjustment;
use Webkul\Inventory\Models\InventoryAdjustmentProduct;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Http\Resources\Inventory\InventoryAdjustmentAll;
use Webkul\Admin\Http\Requests\Inventory\InventoryAdjustmentRequest;
use Webkul\Admin\Http\Resources\Inventory\InventoryAdjustmentSingle;
use Webkul\Admin\Repositories\Inventory\InventoryAdjustmentRepository;
use Webkul\Admin\Http\Resources\Product\ProductAll;
use Webkul\Inventory\Models\InventoryProduct;

class InventoryAdjustmentController extends BackendBaseController {

    protected $inventoryAdjustmentRepository;

    public function __construct(InventoryAdjustmentRepository $inventoryAdjustmentRepository) {
        $this->inventoryAdjustmentRepository = $inventoryAdjustmentRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function list(Request $request)
    {
        $inventoryAdjustmentRepository=$this->inventoryAdjustmentRepository->list($request);
        $data = new InventoryAdjustmentAll($inventoryAdjustmentRepository); // using  inventoryAdjustmentRepository repository
        return $this->responsePaginatedSuccess($data, null, $request);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(InventoryAdjustmentRequest $request) {
        $request = $request->only('warehouse_id', 'products');
        $request['admin_id'] = auth('admin')->user()->id;
        $request['admin_type'] = 'admin';
        $inventoryAdjustment = $this->inventoryAdjustmentRepository->create($request);

        Event::dispatch('admin.inventoryAdjutment.created', $inventoryAdjustment);
        Event::dispatch('admin.log.activity', ['create', 'inventoryAdjustment', $inventoryAdjustment, auth('admin')->user(), $inventoryAdjustment]);

        // send notification to operation manager
        $payload['model'] = $inventoryAdjustment;
        Event::dispatch('admin.alert.admin_create_adjustment_order', [auth('admin')->user(), $payload]);

        return $this->responseSuccess($inventoryAdjustment);
    }

    public function searchProduct(Request $request) {
        $request = $request->only('key', 'warehouse_id');
        $inventoryAdjustments = $this->inventoryAdjustmentRepository->searchProduct($request);

        $data=  new ProductAll ($inventoryAdjustments);
      
        Event::dispatch('admin.inventoryAdjustment.searched', $inventoryAdjustments);
        return $this->responseCustomPaginatedSuccess($inventoryAdjustments, null, $request);
    }

    public function selectProduct(Product $product, Request $request) {
        $request = $request->only('warehouse_id');
        $request['product'] = $product;

        $emptySkuOfProduct = InventoryProduct::where(['warehouse_id' => $request['warehouse_id'], 'product_id' => $product->id])->count();
        if ($emptySkuOfProduct == 0) {
            throw new ResponseErrorException(406, ' لم يتم اضافة هذا المنتج للمخزن بعد   ! ');
        }

        $inventoryTransactions = $this->inventoryAdjustmentRepository->selectProduct($request);
        Event::dispatch('admin.inventoryTransaction.created', $inventoryTransactions);
        return $this->responseSuccess($inventoryTransactions);
    }

    public function showProductSku($sku, Request $request) {
        $request = $request->only('warehouse_id', 'inventory_adjustment_product_id');
        $request['sku'] = $sku;
        $inventoryTransactions = $this->inventoryAdjustmentRepository->showProductSku($sku, $request);
        Event::dispatch('admin.inventoryAdjustment.show', $inventoryTransactions);
        return $this->responseSuccess($inventoryTransactions);
    }
    
    public function deleteProduct($id) {
        $productSku = InventoryAdjustmentProduct::findOrFail($id);
        $inventoryAdjustment = InventoryAdjustment::find($productSku->inventory_adjustment_id);
        if ($inventoryAdjustment->status == InventoryAdjustment::STATUS_CANCELLED || $inventoryAdjustment->status == InventoryAdjustment::STATUS_APPROVED  ) {
            return $this->responseError(422,"You can not remove item from this adujstment !");
        }
        
        if($inventoryAdjustment->adjustmentProducts->count() == 1){
              return $this->responseError(422,"You can only cancel the whole adujstment !");
        }
        
        $inventoryAdjustments = $this->inventoryAdjustmentRepository->deleteProduct($inventoryAdjustment, $productSku);
        
        return $this->responseSuccess($inventoryAdjustments);
    }    

    /**
     * Show the specified supplier.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile($id) {
        $inventoryAdjuestment = $this->inventoryAdjustmentRepository->findOrFail($id);
        $data = new InventoryAdjustmentSingle($inventoryAdjuestment);
        Event::dispatch('admin.inventoryTransaction.show', $inventoryAdjuestment);
        return $this->responseSuccess($data);
    }

    public function setStatus($id, Request $request) {
        $inventoryAdjustment = $this->inventoryAdjustmentRepository->findOrFail($id);
        $before = deep_copy($inventoryAdjustment);

        $request = $request->only('status');
        $request['admin_id'] = auth('admin')->user()->id;
        $request['inventoryAdjustments'] = $inventoryAdjustment;

        // Check Adjustment Status
        $this->validateAdjustmentStatus($inventoryAdjustment, $request['status']);

        $inventoryAdjustment = $this->inventoryAdjustmentRepository->setStatus($request);

        Event::dispatch('admin.inventoryAdjustment.statusChanged', $inventoryAdjustment);
        Event::dispatch('admin.log.activity', ['update-status', 'inventoryAdjustment', $inventoryAdjustment, auth('admin')->user(), $inventoryAdjustment, $before]);

        return $this->responseSuccess($inventoryAdjustment);
    }

    /**
     * @param InventoryAdjustment $inventoryAdjustment
     * @param string $status
     * 
     * @return void
     */
    private function validateAdjustmentStatus(InventoryAdjustment $inventoryAdjustment, string $status) {
        if (in_array($inventoryAdjustment->status, [InventoryAdjustment::STATUS_CANCELLED, InventoryAdjustment::STATUS_APPROVED])) {
            throw new ResponseErrorException(406, 'عذراً لقد تم الانتهاء من هذا الطلب من قبل');
        }
    }

    protected function responseCustomPaginatedSuccess($data, $message = null, $request) {
        $data->status = 200;
        $data->success = true;
        $data->message = $message;

        return response()->json($data);
    }

}
