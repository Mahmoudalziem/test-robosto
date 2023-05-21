<?php

namespace Webkul\Admin\Http\Controllers\SMSCampaign;

use Illuminate\Http\Request;
use App\Jobs\PublishSmsCampaign;
use App\Jobs\SMSNotifier;
use Webkul\Core\Models\Notifier;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Models\SmsCampaign;
use Webkul\Customer\Models\Customer;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Webkul\Customer\Http\Controllers\Auth\SMSTrait;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Http\Resources\SmsCampaign\SmsCampaignAll;
use Webkul\Admin\Http\Requests\SmsCampaign\SmsCampaignRequest;
use Webkul\Admin\Repositories\SmsCampaign\SmsCampaignRepository;

class SMSCampaignController extends BackendBaseController {

    use SMSTrait;

    protected $smsCampaign;

    public function __construct(SmsCampaignRepository $smsCampaign) {
        $this->smsCampaign = $smsCampaign;
    }

    public function index(Request $request) {

        $smsCampaign = $this->smsCampaign->list($request);
        $data['campiagns'] = new SmsCampaignAll($smsCampaign);

        $data['sms_count'] = $this->checkCredit();

        return $this->customResponsePaginatedSuccess($data, null, $request);

        return $this->responsePaginatedSuccess($data, null, $request);
    }

    /**
     * @param $data
     * @param null $message
     * @param $request
     * @return JsonResponse
     */
    protected function customResponsePaginatedSuccess($data, $message = null, $request) {
        $response = null;
        if ($data['campiagns']->resource instanceof LengthAwarePaginator) {
            $response = $data['campiagns']->toResponse($request)->getData();
        }

        $response->sms_count = $data['sms_count'];
        $response->status = 200;
        $response->success = true;
        $response->message = $message;

        return response()->json($response);
    }

    public function create(SmsCampaignRequest $request) {
        $data = $request->only('content', 'scheduled_at', 'tags', 'filter', 'customers');
        $data['admin_id']=auth()->guard("admin")->user()->id;
        $smsCampaign = $this->smsCampaign->create($data);
        if (!isset($data['scheduled_at']) || empty($data['scheduled_at'])) {

            // Send Now
            PublishSmsCampaign::dispatch($smsCampaign);
        }
        return $this->responseSuccess($smsCampaign, 'New Compaing has been created!');
    }

    /**
     * @param SmsCampaign $SmsCampaign
     *
     * @return bool
     */
    public function publishSmsCampaigns(SmsCampaign $smsCampaign) {
        $customers = [];
        $customersIDs = [];
        if (count($smsCampaign->tags)) {
            // Get Notification with Tags and Customers
            $smsCampaignWithTags = $smsCampaign->tags()->with(['customers'])->get();

            foreach ($smsCampaignWithTags as $tag) {
                $filters = $smsCampaign->filter;

                $query = $tag->customers();

                // get customers by $filters;
                if ($filters && !empty($filters)) {

                    // get customers by phones;
                    if (array_key_exists('phone', $filters) && $filters['phone'] != 'all') { //  $filters['phone'] is array
                        $query->whereIn('phone', $filters['phone']);
                    }

                    // filter by gender
                    if (array_key_exists('gender', $filters) && $filters['gender'] != 'all') { // 1 female ,0 male
                        $query->where('gender', (int) $filters['gender']);
                    }

                    // filter by area
                    if (array_key_exists('area_id', $filters) && $filters['area_id'] != 'all') {
                        $query->whereHas('addresses', function ($q) use ($filters) {
                            $q->whereHas('area', function ($q) use ($filters) {
                                $q->where('id', '=', $filters['area_id']);
                            });
                        });
                    }

                    // filter by channel
                    if (array_key_exists('channel_id', $filters) && $filters['channel_id'] != 'all') {
                        $query->where('channel_id', '=', (int) $filters['channel_id']);
                    }

                    if (array_key_exists('date_from', $filters) && $filters['date_from'] && array_key_exists('date_to', $filters) && $filters['date_to']) {
                        $dateFrom = $filters['date_from'] . ' 00:00:00';
                        $dateTo = $filters['date_to'] . ' 23:59:59';
                        $query->where('customers.created_at', '>=', $dateFrom)
                                ->where('customers.created_at', '<=', $dateTo);
                    }
                }

                $customers = array_merge($customers, $query->get()->pluck('id')->toArray());
            }

            $customersIDs = array_unique($customers);
        } elseif (count($smsCampaign->customers)) {
            $customersIDs = $smsCampaign->customers->pluck('id')->toArray();
        }

        // Store Customers who will be notify
        if (count($customersIDs)) {
            // Save Customers who will be send sms
            saveNotifiers($customersIDs, Notifier::SMS_TYPE, $smsCampaign->id);
            
            // Fire the job that will be send sms
            SMSNotifier::dispatch($smsCampaign);
        }
        return true;

    }

    /**
     * @param SmsCampaign $SmsCampaign
     *
     * @return bool
     */
    public function sendToNotifiers(SmsCampaign $smsCampaign)
    {
        $notifiers = $this->getNotifiers($smsCampaign);
        Log::info($notifiers);
        // if no notifiers, then delete old and was sent
        if ($notifiers->isEmpty()) {
            $this->purgeOldNotifiers($smsCampaign);
            return true;
        }
        // $this->updateNotifiers($smsCampaign, $notifiers->pluck('customer_id')->toArray());
        $customersWasSent = [];
        $customers = Customer::whereIn('id', $notifiers->pluck('customer_id')->toArray())->get();

        foreach ($customers as $customer) {
            $text = replaceHashtagInText($smsCampaign->content, $customer);
            $this->sendSMS($customer->phone, $text);
            Log::info("sending to ".$customer->phone."-".$customer->id);
            $customersWasSent[] = $customer->id;            
        }

        // Update Notifiers to SENT
        $this->updateNotifiers($smsCampaign, $customersWasSent);

        SMSNotifier::dispatch($smsCampaign);
        return true;
    }

    /**
     * @param SmsCampaign $SmsCampaign
     *
     * @return Collection
     */
    public function getNotifiers(SmsCampaign $smsCampaign)
    {
        return Notifier::where('entity_type', Notifier::SMS_TYPE)
                                ->where('entity_id', $smsCampaign->id)
                                ->where('sent', 0)
                                ->limit(40)
                                ->get();
    }
    
    /**
     * @param SmsCampaign $SmsCampaign
     *
     * @return void
     */
    public function purgeOldNotifiers(SmsCampaign $smsCampaign)
    {
        Notifier::where('entity_type', Notifier::SMS_TYPE)
                                ->where('entity_id', $smsCampaign->id)
                                ->delete();
    }

    /**
     * @param SmsCampaign $smsCampaign
     * @param array $customer
     * 
     * @return void
     */
    public function updateNotifiers(SmsCampaign $smsCampaign, $customers)
    {
        Notifier::where('entity_type', Notifier::SMS_TYPE)
            ->where('entity_id', $smsCampaign->id)
            ->whereIn('customer_id', $customers)
            ->update(['sent'   => 1]);
    }


}
