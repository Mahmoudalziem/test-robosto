<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class ProductsPricesRep {
    
    public $name;
    protected $data;

    public function __construct(array $data ) {
        $this->name = "products-prices";
        $this->data = $data;
    }
        

    public function getMappedQuery() {

        $lang = $this->data['lang'];
        $select = " SELECT  PT.name product, barcode , products.price price
                    FROM product_translations PT 
                    INNER JOIN products  
                    on PT.product_id = products.id
                    where PT.locale =  '" . $lang . "' ";  
        
        $select=preg_replace( "/\r|\n/", "", $select );
        
        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['product'] = $item->product;
                    $data['barcode'] = '(' . (string) $item->barcode .')' ; 
                    $data['price'] = $item->price;

                    return $data;
                }, $query
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'Product', 'Barcode', 'Price'];
    }

    public function getName() {
        return  $this->name ;
    }
    
    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName().'.xlsx');
    }     

}
