<?php

namespace Webkul\Admin\Services\Report\type;

use Webkul\Admin\Services\Report\ExportReprotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Admin\Exports\Reports\ExportReport;

class OperationManagersWalletRep {

    public $name;
    protected $data;

    public function __construct(array $data) {
        $this->name = "Operation Managers Wallets";
        $this->data = $data;
    }

    public function getMappedQuery() {

        $lang = $this->data['lang'];

        $select = "SELECT AD.name, ART.name AS area, AMW.wallet, AMW.total_wallet, AMW.pending_wallet
                    FROM admins AD
                        INNER JOIN area_manager_wallet AMW ON AMW.area_manager_id = AD.id
                        INNER JOIN admin_areas AAR ON AAR.admin_id = AD.id
                        INNER JOIN area_translations ART ON ART.area_id = AAR.area_id
                        INNER JOIN admin_roles AR ON AR.admin_id = AD.id
                        INNER JOIN roles R ON R.id = AR.role_id
                        INNER JOIN role_translations RT ON RT.role_id = R.id
                    WHERE 
                        RT.locale = '{$lang}' AND ART.locale = '{$lang}' AND R.slug = 'area-manager'";

        $select = preg_replace("/\r|\n/", "", $select);

        $query = collect(DB::select(DB::raw($select)));
        $counter = 1;
        $mappedQuery = $query->map(function ($item) use (&$counter) {
                    $data['#'] = $counter++;
                    $data['name'] = $item->name;
                    $data['area'] =   $item->area;
                    $data['wallet'] =   $item->wallet;
                    $data['total_wallet'] =   $item->total_wallet;
                    $data['pending_wallet'] =   $item->pending_wallet;
                    return $data;
                }
        );
        return $mappedQuery;

    }

    public function getHeaddings() {
        return ['#', 'Name' , 'Area' , 'Wallet' , 'Total Wallet' , 'Pending Wallet'];
    }

    public function getName() {
        return $this->name;
    }

    public function download() {
        return Excel::download(new ExportReport($this->getMappedQuery(), $this->getHeaddings()), $this->getName() . '.xlsx');
    }

}
