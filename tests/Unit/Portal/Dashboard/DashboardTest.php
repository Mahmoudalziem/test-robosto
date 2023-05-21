<?php

namespace Tests\Unit\Portal\Dashboard;

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Support\Str;
use Webkul\User\Models\Admin;
use Webkul\Sales\Models\Order;
use Webkul\Product\Models\Product;
use Webkul\Category\Models\Category;
use Illuminate\Support\Facades\Event;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Promotion\Models\Promotion;
use Webkul\Inventory\Models\InventoryProduct;

class DashboardTest extends TestCase
{
    private $admin;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::find(1);
    }

    public function testGettingTotalWarehouses()
    {
        $countData = Warehouse::count();

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.dashboard.overview.overview-summary.totalStores'))
            ->assertStatus(200)->assertJsonStructure(['status', 'success', 'data'])
            ->assertJsonPath('data', $countData);
    }

    public function testGettingTotalCategories()
    {
        $countData = Category::count();

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.dashboard.overview.overview-summary.totalCategories'))
            ->assertStatus(200)->assertJsonStructure(['status', 'success', 'data'])
            ->assertJsonPath('data', $countData);
    }

    public function testGettingTotalProducts()
    {
        $countData = Product::count();

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.dashboard.overview.overview-summary.totalItems'))
            ->assertStatus(200)->assertJsonStructure(['status', 'success', 'data'])
            ->assertJsonPath('data', $countData);
    }

    public function testGettingTotalProductsOfStock()
    {
        $fromDate = Carbon::now()->startOfDay()->toDateString();
        $tillDate = Carbon::now()->addMonth()->startOfDay()->toDateString();
        $countData = InventoryProduct::whereBetween('exp_date', [$fromDate, $tillDate])->groupBy('product_id')->count();

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.dashboard.overview.overview-summary.itemsOutOfStock'))
            ->assertStatus(200)->assertJsonStructure(['status', 'success', 'data'])
            ->assertJsonPath('data', $countData);
    }

    public function testGettingAvgOrderPrice()
    {
        $countData = Order::avg('final_total');

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.dashboard.overview.overview-summary.avgOrdersPrice'))
            ->assertStatus(200)->assertJsonStructure(['status', 'success', 'data'])
            ->assertJsonPath('data', $countData);
    }

    public function testGettingValidPromotion()
    {
        Carbon::now()->toDateString();
        $countData = Promotion::where('start_validity', '<=', Carbon::now()->toDateString())
            ->where('end_validity', '>=', Carbon::now()->toDateString())
            ->orWhere('start_validity', null)->orWhere('end_validity', null)
            ->orWhere('is_valid', 1)->count();

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.dashboard.overview.overview-summary.validPromotions'))
            ->assertStatus(200)->assertJsonStructure(['status', 'success', 'data'])
            ->assertJsonPath('data', $countData);
    }

    public function testGettingProductsExpiredSoon()
    {
        $fromDate = Carbon::now()->startOfDay()->toDateString();
        $tillDate = Carbon::now()->addMonth()->startOfDay()->toDateString();
        $countData = InventoryProduct::whereBetween('exp_date', [$fromDate, $tillDate])->groupBy('product_id')->count();

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.dashboard.overview.overview-summary.itemsExpiredSoon'))
            ->assertStatus(200)->assertJsonStructure(['status', 'success', 'data'])
            ->assertJsonPath('data', $countData);
    }

    public function testGettingCategoriesSoldCount()
    {
        $countData = Category::with('translation')->active()->orderBy('sold_count')->take(5)->get();

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.dashboard.overview.ranking-data.category', 'asc'))
            ->assertStatus(200)->assertJsonStructure([
                'status', 'success',
                'data'  =>  [
                    0   =>  [
                        "id",
                        "position",
                        "sold_count",
                        "image",
                        "thumb",
                        "status",
                        "created_at",
                        "updated_at",
                        "image_url",
                        "thumb_url",
                        "name",
                        "translation"
                    ]
                ]
            ]);
    }

    public function testGettingSoldProductsSorted()
    {
        $countData = Product::count();

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.dashboard.overview.ranking-data.products', 'asc'))
            ->assertStatus(200)->assertJsonStructure([
                'status', 'success',
                'data'  =>  [
                    0   =>  [
                        "product_id",
                        "total_sold",
                        "item"
                    ]
                ]
            ]);
    }

    public function testGettingAreaOrders()
    {

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.dashboard.overview.ranking-data.areas', 'asc'))
            ->assertStatus(200)->assertJsonStructure([
                'status', 'success',
                'data'  =>  [
                    0   =>  [
                        "area_id",
                        "total_orders",
                        "status_name",
                        "order_flagged",
                        "expected_date",
                        "expected_delivered_date",
                        "delivered_at",
                        "preparing_at",
                        "prepared_time",
                        "flagged_at",
                        "area",
                        "estimated_logs",
                        "actual_logs",
                    ]
                ]
            ]);
    }

    public function testGettingStoreOrders()
    {

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.dashboard.overview.ranking-data.stores', 'asc'))
            ->assertStatus(200)->assertJsonStructure([
                'status', 'success',
                'data'  =>  [
                    0   =>  [
                        "warehouse_id",
                        "total_orders",
                        "status_name",
                        "order_flagged",
                        "expected_date",
                        "expected_delivered_date",
                        "delivered_at",
                        "preparing_at",
                        "prepared_time",
                        "flagged_at",
                        "warehouse",
                        "estimated_logs",
                        "actual_logs",
                    ]
                ]
            ]);
    }

    public function testGettingExpDateItem()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.dashboard.overview.ranking-data.exp-date-of-items', 'asc'))
            ->assertStatus(200)->assertJsonStructure([
                'status', 'success',
                'data'  =>  [
                    0   =>  [
                        "id",
                        "sku",
                        "prod_date",
                        "exp_date",
                        "qty",
                        "cost_before_discount",
                        "cost",
                        "amount_before_discount",
                        "amount",
                        "product_id",
                        "warehouse_id",
                        "area_id",
                        "created_at",
                        "updated_at",
                        "price",
                        "product",
                    ]
                ]
            ]);
    }

    public function testGettingProductsVisited()
    {

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.dashboard.overview.ranking-data.no-of-visits', 'asc'))
            ->assertStatus(200)->assertJsonStructure([
                'status', 'success',
                'data'  =>  [
                    0   =>  [
                        "id",
                        "barcode",
                        "prefix",
                        "image",
                        "thumb",
                        "featured",
                        "status",
                        "returnable",
                        "price",
                        "cost",
                        "tax",
                        "weight",
                        "width",
                        "height",
                        "length",
                        "sold_count",
                        "visits_count",
                        "shelve_id",
                        "brand_id",
                        "unit_id",
                        "unit_value",
                        "created_at",
                        "updated_at",
                        "image_url",
                        "thumb_url",
                        "total_in_stock",
                        "name",
                        "description",
                        "translations"
                    ]
                ]
            ]);
    }


    public function testGettingProductsVisitedPerCustomer()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.dashboard.overview.ranking-data.no-of-visits-per-customer', 'asc'))
            ->assertStatus(200)->assertJsonStructure([
                'status', 'success', 'data'
            ]);
    }
}
