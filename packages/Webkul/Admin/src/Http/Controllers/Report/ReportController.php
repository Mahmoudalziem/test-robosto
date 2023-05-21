<?php

namespace Webkul\Admin\Http\Controllers\Report;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Webkul\Admin\Services\Report\ExportReprotFactory;
use Webkul\Core\Http\Controllers\BackendBaseController;

class ReportController extends BackendBaseController {

    protected $reportRepository;

    // by rule
    public function listReports(Request $request) {
        $user = auth()->user();
        $availableReports = $this->listAvailableReportsByRole($user->roles);
        return $this->responseSuccess($availableReports, null, $request);
    }

    public function export(Request $request) {
        $user = auth()->user();
        $availableReports = $this->listAvailableReportsByRole($user->roles);
        $hasAccess = false;
        foreach ($availableReports as $rep) {
            if ($rep['type'] == $request['type']) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            return $this->responseError(403, "You have no access to run this report");
        }

        $data['type'] = $request['type'];
        $data['area'] = $request['area'];
        $data['date_from'] = $request['date_from'];
        $data['date_to'] = $request['date_to'];
        $data['subcategory'] = $request['subcategory'];
        $data['lang'] = 'ar';
        $expoertReport = new ExportReprotFactory($data);

        return $expoertReport->download();
    }

    private function listAvailableReportsByRole($roles): array {
        $roles = $roles->pluck('slug')->toArray();
        $reports = [];
        foreach ($roles as $role) {
            $myReps = Config::get('permissions.reports.' . $role);
            if($myReps){
                $reports = array_merge($reports, $myReps);
            }
        }
        $uniqueReports = array_unique($reports);
        $reports = array_map(function ($rep) {
            $label = __('admin::report.' . $rep);
            return ['type' => $rep, 'label' => $label];
        }, $uniqueReports);

        return array_values($reports);
    }

}
