<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;
use Webkul\Area\Models\Area;

class OldInventoryProductVer2Rep {

    public $name;
    protected $data;
    private $headings = ['#', 'Barcode', 'Name', 'Weight'];
    private $areas;

    public function __construct(array $data) {
        $this->name = "old-inventory-product-ver-2";
        $this->data = $data;
        $areaId = $this->data['area'] ?? null;
        if ($areaId) {
            $this->areas = Area::where('id', $areaId)->get('id', 'name');
        } else {
            $this->areas = Area::get('id', 'name');
        }
    }

    public function getMappedQuery() {

        $lang = $this->data['lang'];
        $areaId = $this->data['area'] ?? null;
 
        $sub = '';
        $split = ',';
        foreach ($this->areas->toArray() as $k => $area) {

            if (($this->areas->count() - $k) == 1) {
                $split = '';
            }

            array_push($this->headings, $area['name']);
            $sub .= "MAX(CASE WHEN inventory_areas.area_id = " . $area['id'] . " THEN inventory_areas.total_qty END)  '" . $area['name'] . "' " . $split;
        }
        $sub = preg_replace("/\r|\n/", "", $sub);

        $select = "SELECT inventory_areas.product_id, p_barcode, p_name, p_weight, " . $sub . " 
                    FROM inventory_areas  
                    INNER JOIN (
                            SELECT p.id,p.barcode p_barcode,pt.name p_name,p.weight p_weight FROM products p
                                INNER JOIN product_translations pt ON p.id= pt.product_id
                                WHERE pt.locale = '" . $lang . "' 
                            ) prods
                        ON inventory_areas.product_id= prods.id
                    GROUP BY inventory_areas.product_id,p_barcode,p_name";

        $select = preg_replace("/\r|\n/", "", $select);
        $select = preg_replace("/\t+/", "", $select);
        
        $query = collect(DB::select(DB::raw($select)));

        $counter = 1;
        $mappedQuery = $query->map(
                function ($item) use (&$counter) {

                    $data['#'] = $counter++;
                    $data['p_barcode'] = '(' . (string) $item->p_barcode . ')';
                    $data['p_name'] = $item->p_name;
                    $data['p_weight'] = $item->p_weight;
                    foreach ($this->areas->toArray() as $area) {
                        $data[$area['name']] = $item->{$area['name']} == 0 ? '0' : $item->{$area['name']};
                    }
                    return $data;
                }, $query
        );
        

        return $mappedQuery;
    }

    public function getHeaddings() {
        return $this->headings;
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
