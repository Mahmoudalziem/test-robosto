<?php

namespace Webkul\Admin\Http\Controllers\PurchaseOrder;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Webkul\Admin\Http\Resources\PurchaseOrder\PurchaseOrderProductsSearch;
use Webkul\Admin\Http\Resources\PurchaseOrder\PurchaseOrderWarehousesSearch;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Http\Requests\PurchaseOrder\PurchaseOrderRequest;
use Webkul\Admin\Http\Resources\PurchaseOrder\PurchaseOrder;
use Webkul\Admin\Http\Resources\PurchaseOrder\PurchaseOrderSingle as PurchaseOrderResource;
use Webkul\Admin\Repositories\Product\ProductRepository;
use Webkul\Admin\Repositories\PurchaseOrder\PurchaseOrderRepository;
use Webkul\Inventory\Models\InventoryArea;
use Webkul\Product\Models\Product;
use function DeepCopy\deep_copy;
use Webkul\User\Models\Role;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\POExport;

class PurchaseOrderController extends BackendBaseController
{
    /**
     * PurchaseOrderRepository object
     *
     * @var PurchaseOrderRepository
     */
    protected $purchaseOrderRepository;

    /**
     * Create a new controller instance.
     *
     * @param PurchaseOrderRepository $purchaseOrderRepository
     * @return void
     */
    public function __construct(PurchaseOrderRepository $purchaseOrderRepository) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $purchaseOrders = $this->purchaseOrderRepository->list($request);

        $purchaseOrders = new PurchaseOrder($purchaseOrders);

        return $this->responsePaginatedSuccess($purchaseOrders, null, $request);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param PurchaseOrderRequest $request
     * @return JsonResponse
     */
    public function create(PurchaseOrderRequest $request)
    {
        $data = $request->only(['invoice_no', 'is_draft', 'discount_type', 'discount', 'warehouse_id', 'area_id', 'supplier_id', 'products']);

        // perevent some admin to save draft
        $admin=auth()->guard("admin")->user();
        $data['admin_id']=$admin->id;
        // user can create but he has save-draft-only
        if($admin->rolePermissionExists("admin.inventory.purchase-order.draft-only") && $data['is_draft'] == 0){
           return $this->responseError(422,"You only can save draft",['errors'=>[]]);  
        }
        
        $purchaseOrder = $this->purchaseOrderRepository->create($data);

        Event::dispatch('purchaseOrder.created', $purchaseOrder);
        Event::dispatch('admin.log.activity', ['create', 'purchaseOrder', $purchaseOrder, auth('admin')->user(), $purchaseOrder]);

        // send notification to operation manager
        $payload['model']=$purchaseOrder;
        Event::dispatch('admin.alert.admin_create_purchase_order', [ auth('admin')->user() ,$payload ]);        
        
        return $this->responseSuccess($purchaseOrder);
    }
    
    public function draftOnly(Request $request) {
          return $this->responseSuccess(); 
    }

    /**
     * Show the specified purchaseOrder.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $purchaseOrder = $this->purchaseOrderRepository->show($id);

        Event::dispatch('purchaseOrder.show', $purchaseOrder);

        $purchaseOrder = new PurchaseOrderResource($purchaseOrder);

        return $this->responseSuccess($purchaseOrder);

    }

    /**
     * Show the specified purchaseOrder.
     *
     * @param PurchaseOrderRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(PurchaseOrderRequest $request, $id)
    {
        $data = $request->only(['invoice_no', 'is_draft', 'discount_type', 'discount', 'warehouse_id', 'area_id', 'supplier_id', 'products']);
        $data['admin_id']=auth()->guard("admin")->user()->id;
        
        $purchaseOrder = $this->purchaseOrderRepository->findOrFail($id);

        if ($purchaseOrder->is_draft == 0) {
            return $this->responseError(400, 'Cannot update purchaseOrder to issued');
        }

        $before = deep_copy($purchaseOrder);

        $purchaseOrder = $this->purchaseOrderRepository->update($purchaseOrder, $data);

        Event::dispatch('purchaseOrder.update-status-to-issued', $purchaseOrder);
        Event::dispatch('admin.log.activity', ['update', 'purchaseOrder', $purchaseOrder, auth('admin')->user(), $purchaseOrder, $before]);

        return $this->responseSuccess($purchaseOrder);
    }

    /**
     * Show the specified purchaseOrder.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function updateToIssued($id)
    {
        $purchaseOrder = $this->purchaseOrderRepository->findOrFail($id);

        if ($purchaseOrder->is_draft == 0 || $purchaseOrder->is_draft == 2) {
            return $this->responseError(400, 'Cannot update purchaseOrder to issued');
        }

        $before = deep_copy($purchaseOrder);
        $purchaseOrder = $this->purchaseOrderRepository->updateDraftToIssued($purchaseOrder,auth()->guard("admin")->user()->id);

        Event::dispatch('purchaseOrder.update-status-to-issued', $purchaseOrder);
        Event::dispatch('admin.log.activity', ['update-status', 'purchaseOrder', $purchaseOrder, auth('admin')->user(), $purchaseOrder, $before]);

        $purchaseOrder = new PurchaseOrderResource($purchaseOrder);

        return $this->responseSuccess($purchaseOrder);
    }
    
    /**
     * Show the specified purchaseOrder.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function updateToCancelled($id)
    {
        $purchaseOrder = $this->purchaseOrderRepository->findOrFail($id);

        
        if ($purchaseOrder->is_draft == 0 || $purchaseOrder->is_draft == 2 ) { // 0 issed 1 draft 2 cancelled
            return $this->responseError(400, 'Cannot update purchaseOrder to issued because it is already cancelled');
        }
        
        $before = deep_copy($purchaseOrder);

        $purchaseOrder = $this->purchaseOrderRepository->updateDraftToCancelled($purchaseOrder,auth()->guard("admin")->user()->id);

        Event::dispatch('purchaseOrder.update-status-to-cancelled', $purchaseOrder);
        Event::dispatch('admin.log.activity', ['update-status', 'purchaseOrder', $purchaseOrder, auth('admin')->user(), $purchaseOrder, $before]);

        $purchaseOrder = new PurchaseOrderResource($purchaseOrder);

        return $this->responseSuccess($purchaseOrder);
    }    

    /**
     * Display a listing of the resource when Search.
     *
     * @param Request $request
     * @param ProductRepository $productRepository
     * @return JsonResponse
     */
    public function productsSearch(Request $request, ProductRepository $productRepository)
    {
        $products = $productRepository->whereTranslationLike('name', '%'.$request->q.'%')->orWhere('barcode', $request->q);

        $perPage = $request->has('per_page') ? (int) $request->per_page : 30;
        $pagination = $products->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        $data = new PurchaseOrderProductsSearch($pagination);

        return $this->responsePaginatedSuccess($data, null, $request);
    }

    /**
     * Display a listing of the resource when Search.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function warehousesSearch(Request $request)
    {
        $products = $this->purchaseOrderRepository->warehousesSearch($request);

        $data = new PurchaseOrderWarehousesSearch($products);

        return $this->responsePaginatedSuccess($data, null, $request);
    }



    public function export($id){
        return Excel::download(new POExport($this->purchaseOrderRepository , $id), "PO-{$id}.xlsx");
    }
}