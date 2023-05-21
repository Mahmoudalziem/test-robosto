<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class DriversWalletRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "Drivers Wallets";
        $this->data = $data;
    }

    public function getMappedQuery() {

        $lang = $this->data['lang'];

        $select = "SELECT D.name, ART.name area, WHT.name warehouse , D.wallet, D.total_wallet 
                    FROM drivers D
                        INNER JOIN area_translations ART ON ART.area_id = D.area_id
                        INNER JOIN warehouse_translations WHT ON WHT.warehouse_id = D.warehouse_id
                    WHERE ART.locale = '{$lang}' AND WHT.locale = '{$lang}'";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['name'] = $item->name;
                    $data['area'] =   $item->area;
                    $data['warehouse'] =   $item->warehouse;
                    $data['wallet'] =   $item->wallet;
                    $data['total_wallet'] =   $item->total_wallet;
                    return $data;
                }
        );
        return $mappedQuery;
    }

    public function getHeaddings() {
        return ['#', 'Name', 'Area', 'Warehouse', 'Wallet', 'Total Wallet'];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
