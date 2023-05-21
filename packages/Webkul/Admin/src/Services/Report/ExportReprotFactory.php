<?php

namespace Webkul\Admin\Services\Report;

use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Services\Report\type\RevenueRep;
use Webkul\Admin\Services\Report\type\OrdersCountRep;
use Webkul\Admin\Services\Report\type\DriverLoginsRep;
use Webkul\Admin\Services\Report\type\StockAndSoldRep;
use Webkul\Admin\Services\Report\type\BasketAverageRep;
use Webkul\Admin\Services\Report\type\DriversWalletRep;
use Webkul\Admin\Services\Report\type\OrdersPerHourRep;
use Webkul\Admin\Services\Report\type\AdjustmentCostRep;
use Webkul\Admin\Services\Report\type\ProductsPricesRep;
use Webkul\Admin\Services\Report\type\DiscountCostPerRep;
use Webkul\Admin\Services\Report\type\DiscountCostValRep;
use Webkul\Admin\Services\Report\type\OrdersWithBooksRep;
use Webkul\Admin\Services\Report\type\InventoryControlRep;
use Webkul\Admin\Services\Report\type\InventoryProductRep;
use Webkul\Admin\Services\Report\type\ProuductMostSoldRep;
use Webkul\Admin\Services\Report\type\OrdersWithNoBooksRep;
use Webkul\Admin\Services\Report\type\ProductsSoldCountRep;
use Webkul\Admin\Services\Report\type\AvgCustomersOrdersRep;
use Webkul\Admin\Services\Report\type\CountCustomersOrdersRep;
use Webkul\Admin\Services\Report\type\InventoryProductVer2Rep;
use Webkul\Admin\Services\Report\type\InvProdSkuBarcodeQtyRep;
use Webkul\Admin\Services\Report\type\ProductsWithNoOrdersRep;
use Webkul\Admin\Services\Report\type\AvgDeliveryTimeByAreaRep;
use Webkul\Admin\Services\Report\type\DeliveryTimeWithOrderRep;
use Webkul\Admin\Services\Report\type\BasketAverageRepVerTowRep;
use Webkul\Admin\Services\Report\type\OldInventoryProductVer2Rep;
use Webkul\Admin\Services\Report\type\OperationManagersWalletRep;
use Webkul\Admin\Services\Report\type\CountUpdatedOrdersInAreaRep;
use Webkul\Admin\Services\Report\type\FirstOrderCreatedInMonthRep;
use Webkul\Admin\Services\Report\type\DeliveryTimeDelayMoreThanHourRep;
use Webkul\Admin\Services\Report\type\DeliveryTimeDelayMoreThanTwoHourRep;
use Webkul\Admin\Services\Report\type\ProductsWithPricesAndLastPOpriceRep;
use Webkul\Admin\Services\Report\type\OrdersCountPerHourWithDeliveryTimeRep;
use Webkul\Admin\Services\Report\type\InventoryWarehouseAmount;
use Webkul\Admin\Services\Report\type\InventoryWarehouseStockValue;
use Webkul\Admin\Services\Report\type\PurchaseFirstOrder;
use Webkul\Admin\Services\Report\type\OrdersRatingRep;
use Webkul\Admin\Services\Report\type\OrdersViolationRep;
use Webkul\Admin\Services\Report\type\UpdatedOrdersWithNoBooksRep;
use Webkul\Admin\Services\Report\type\DriverIncentiveRep;
use Webkul\Admin\Services\Report\type\OperationsRep;
use Webkul\Admin\Services\Report\type\PurchaseAtleastFiveOrders;
use Webkul\Admin\Services\Report\type\RobostoSuppliersRep;
use Webkul\Admin\Services\Report\type\AmountCollectedRep;
use Webkul\Admin\Services\Report\type\AdjustmentReportRep;
use Webkul\Admin\Services\Report\type\TimeToZeroRep;
use Webkul\Admin\Services\Report\type\StockAndSoldSpesifiedProductsRep;

class ExportReprotFactory
{

    protected $type;
    protected $report;
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->getType();
    }

    public function getType()
    {

        switch ($this->data['type']) {
            case "inventory-product":
                $this->report = new InventoryProductRep($this->data);
                break;
            case "inventory-product-ver-2":
                $this->report = new InventoryProductVer2Rep($this->data);
                break;
            case "old-inventory-product-ver-2":
                $this->report = new OldInventoryProductVer2Rep($this->data);
                break;
            case "inventory-warehouse-amount":
                $this->report = new InventoryWarehouseAmount($this->data);
                break;
            case "inventory-warehouse-stock-value":
                $this->report = new InventoryWarehouseStockValue($this->data);
                break;

            case "product-most-sold":
                $this->report = new ProuductMostSoldRep($this->data);
                break;
            case "products-prices":
                $this->report = new ProductsPricesRep($this->data);
                break;
            case "products-sold-count":
                $this->report = new ProductsSoldCountRep($this->data);
                break;
            case "stock-and-sold":
                $this->report = new StockAndSoldRep($this->data);
                break;
            case "orders-count":
                $this->report = new OrdersCountRep($this->data);
                break;
            case "driver-logins":
                $this->report = new DriverLoginsRep($this->data);
                break;
            case "delivery-time-with-order":
                $this->report = new DeliveryTimeWithOrderRep($this->data);
                break;
            case "basket-average":
                $this->report = new BasketAverageRep($this->data);
                break;
            case "basket-average-ver-2":
                $this->report = new BasketAverageRepVerTowRep($this->data);
                break;
            case "delivery-time-delay-more-than-hour":
                $this->report = new DeliveryTimeDelayMoreThanHourRep($this->data);
                break;
            case "delivery-time-delay-more-than-two-hour":
                $this->report = new DeliveryTimeDelayMoreThanTwoHourRep($this->data);
                break;
            case "orders-per-hour":
                $this->report = new OrdersPerHourRep($this->data);
                break;
            case "orders-with-books":
                $this->report = new OrdersWithBooksRep($this->data);
                break;
            case "orders-with-no-books":
                $this->report = new OrdersWithNoBooksRep($this->data);
                break;
            case "prodcuts-with-no-orders":
                $this->report = new ProductsWithNoOrdersRep($this->data);
                break;
            case "orders-count-per-hour-with-delivery-time":
                $this->report = new OrdersCountPerHourWithDeliveryTimeRep($this->data);
                break;
            case "products-with-prices-and-last-po-price":
                $this->report = new ProductsWithPricesAndLastPOpriceRep($this->data);
                break;
            case "first-order-created-in-month":
                $this->report = new FirstOrderCreatedInMonthRep($this->data);
                break;
            case "purchase-first-order":
                $this->report = new PurchaseFirstOrder($this->data);
                break;
            case "purchase-atleast-five-orders":
                $this->report = new PurchaseAtleastFiveOrders($this->data);
                break;

            case "adjustment-cost":
                $this->report = new AdjustmentCostRep($this->data);
                break;
            case "discount-cost-per":
                $this->report = new DiscountCostPerRep($this->data);
                break;
            case "discount-cost-val":
                $this->report = new DiscountCostValRep($this->data);
                break;
            case "revenue":
                $this->report = new RevenueRep($this->data);
                break;
            case "inventory-control":
                $this->report = new InventoryControlRep($this->data);
                break;
            case "avg-delivery-time-by-area":
                $this->report = new AvgDeliveryTimeByAreaRep($this->data);
                break;
            case "inventory-product-barcode-sku-qty":
                $this->report = new InvProdSkuBarcodeQtyRep($this->data);
                break;
            case "count-updated-orders-in-area":
                $this->report = new CountUpdatedOrdersInAreaRep($this->data);
                break;
            case "operation-managers-wallet":
                $this->report = new OperationManagersWalletRep($this->data);
                break;
            case "drivers-wallet":
                $this->report = new DriversWalletRep($this->data);
                break;
            case "count-customers-orders":
                $this->report = new CountCustomersOrdersRep($this->data);
                break;
            case "avg-customers-orders":
                $this->report = new AvgCustomersOrdersRep($this->data);
                break;
            case "orders-rating";
                $this->report = new OrdersRatingRep($this->data);
                break;
            case "orders-violation";
                $this->report = new OrdersViolationRep($this->data);
                break;
            case "updated-orders-with-no-books";
                $this->report = new UpdatedOrdersWithNoBooksRep($this->data);
                break;
            case "drivers-incentive";
                $this->report = new DriverIncentiveRep($this->data);
                break;
            case "robosto-supplier";
                $this->report = new RobostoSuppliersRep($this->data);
            break;
            case "operations";
                $this->report = new OperationsRep($this->data);
                break;
            case "amount-collected";
                $this->report = new AmountCollectedRep($this->data);
                break;    
            case "adjustment-report";
                $this->report = new AdjustmentReportRep($this->data);
                break;
            case "time-to-zero-report";
                $this->report = new TimeToZeroRep($this->data);
                break;
            case "stock-and-sold-specified-products";
                $this->report = new StockAndSoldSpesifiedProductsRep($this->data);
                break;
            default:
                break;
        }
        return $this->report;
    }

    public function download()
    {
        return $this->report->download();
    }
}