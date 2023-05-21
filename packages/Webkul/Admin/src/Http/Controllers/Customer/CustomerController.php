<?php

namespace Webkul\Admin\Http\Controllers\Customer;

use App\Exceptions\ResponseSuccessException;
use App\Jobs\SendTagSMS;
use Illuminate\Support\Arr;
use Webkul\Core\Models\Tag;
use App\Jobs\SetCustomerTag;
use Illuminate\Http\Request;
use Webkul\Area\Models\Area;
use Webkul\User\Models\Role;
use Webkul\Core\Models\Channel;
use App\Jobs\RetentionsNotifier;
use function DeepCopy\deep_copy;
use Webkul\Core\Models\Notifier;
use App\Jobs\SetCustomerSettings;
use Webkul\Customer\Models\Avatar;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Webkul\Customer\Models\Customer;
use Illuminate\Support\Facades\Event;
use Webkul\Customer\Models\PaymobCard;
use Webkul\Customer\Models\WalletNote;
use Webkul\Promotion\Models\Promotion;
use Webkul\Core\Models\RetentionMessage;
use Webkul\Customer\Models\WalletCustomerReason;
use Webkul\Admin\Exports\CustomersExport;
use Webkul\Core\Services\CheckPointInArea;
use Webkul\Customer\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Admin\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Webkul\Customer\Services\Calls\CallCustomer;
use Webkul\Core\Services\SendNotificationUsingFCM;
use Webkul\Customer\Http\Controllers\Auth\SMSTrait;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Http\Resources\Customer\CustomerSingle;
use Webkul\Admin\Http\Resources\Customer\CustomerNotesAll;
use Webkul\Admin\Http\Requests\Customer\CreateGroupRequest;
use Webkul\Admin\Http\Requests\Customer\CustomerCreateRequest;
use Webkul\Admin\Http\Requests\Customer\CustomerUpdateRequest;
use Webkul\Admin\Http\Resources\Customer\CustomerInvitationAll;
use Webkul\Admin\Repositories\Customer\AdminCustomerRepository;
use Webkul\Admin\Http\Requests\Customer\CustomerUpdateNameRequest;
use Webkul\Admin\Http\Requests\Customer\CallcenterCheckPhoneRequest;
use Webkul\Admin\Http\Requests\Customer\CustomerUpdateWalletRequest;
use Webkul\Admin\Http\Requests\Customer\CustomerAddressCreateRequest;
use Webkul\Admin\Http\Requests\Customer\CustomerAddressUpdateRequest;
use Webkul\Admin\Repositories\Customer\AdminCustomerAddressRepository;
use Webkul\Admin\Http\Requests\Customer\CallcenterProfileUpdateRequest;
use Webkul\Admin\Http\Resources\Customer\Customer as CustomerAllResource;
use Webkul\Admin\Http\Resources\Customer\CustomerSingle as CustomerResource;
use Webkul\Promotion\Models\PromotionVoidDevice;
use Webkul\Admin\Http\Resources\Customer\CustomerDevicesAll;

class CustomerController extends BackendBaseController {

    use SMSTrait;

    protected $customerRepository;
    protected $customerAddressRepository;

    protected $guard = 'admin';

    public function __construct(
            AdminCustomerRepository $customerRepository,
            AdminCustomerAddressRepository $customerAddressRepository
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerAddressRepository = $customerAddressRepository;
    }

    public function list(Request $request) {
        $request['count_deactive_status'] = $this->customerRepository->count(['status' => 0]);
        // elastic search
        if ($request['filter'] && !empty($request['filter'])) {
            $customer = $this->customerRepository->search($request); // elasticsearch
        } else {
            $customer = $this->customerRepository->list($request);
        }

        $data = new CustomerAllResource($customer); // using customer repository
        return $this->responsePaginatedSuccess($data, null, $request);
    }

    public function export(Request $request) {
        return Excel::download(new CustomersExport($this->customerRepository), 'customers.xlsx');
    }

    public function setStatus(Customer $customer, Request $request) {
        $request = $request->only('status');
        $before = deep_copy($customer);

        $customer = $this->customerRepository->update($request, $customer->id);

        $data = new CustomerSingle($customer); // using customer repository
        $message = $request['status'] == 1 ? "Customer has bean activated." : "Customer has bean deactivated.";

        Event::dispatch('admin.log.activity', ['update-status', 'customer', $customer, auth('admin')->user(), $customer, $before]);

        return $this->responseSuccess($data, $message);
    }

    protected function responseSearchPaginatedSuccess($data, $message = [], $request) {
        $oVal = (object) [];

        if ($request['count_deactive_status']) {
            $data->count_deactive_status = $request['count_deactive_status'];
        }

        $data->status = 200;
        $data->success = true;
        $data->message = $message;

        return response()->json($data);
    }

    protected function responsePaginatedSuccess($data, $message = [], $request) {
        $oVal = (object) [];
        if ($data->resource instanceof LengthAwarePaginator) {
            $data = $data->toResponse($request)->getData();
        }
        if ($request['count_deactive_status']) {
            $data->count_deactive_status = $request['count_deactive_status'];
        }

        $data->status = 200;
        $data->success = true;
        $data->message = $message;

        return response()->json($data);
    }


    public function add(CustomerCreateRequest $request)
    {
        $data = $request->only('name', 'gender', 'phone', 'landline', 'email', 'area_id', 'addressInfo');



        $data['channel_id'] = Channel::CALL_CENTER; // 1 = call center
        // Get Avatar Data
        $avatar = Avatar::where('gender', $data['gender'])->first();
        if (!$avatar) {
            $avatar = Avatar::first();
        }
        $data['avatar_id'] = $avatar->id;
        $data['avatar'] = $avatar->image;

        $customer = $this->customerRepository->create($data);

        // Save Customer Address
        $this->saveCustomerAddress($customer, $data);


        // set settings for new customer
        SetCustomerSettings::dispatch($customer);

        // set (new-user) tag for new customer
        SetCustomerTag::dispatch($customer);

        Event::dispatch('admin.log.activity', ['create', 'customer', $customer, auth('admin')->user(), $customer]);

        return $this->responseSuccess(null, "New Customer has been created!");
    }

    /**
     * @param mixed $customer
     * @param mixed $data
     * 
     * @return void
     */
    private function saveCustomerAddress($customer, $data)
    {
        if (isset($data['addressInfo']) && count($data['addressInfo']) > 0) {
            $addressInfo = $data['addressInfo'];
            $addressInfo['customer_id'] = (int) $customer->id;
            $addressInfo['latitude'] = $data['addressInfo']['location']['lat'];
            $addressInfo['longitude'] = $data['addressInfo']['location']['lng'];

            if (!isset($data['addressInfo']['phone']) || empty($data['addressInfo']['phone'])) {
                $addressInfo['phone'] = $customer->phone;
            }

            // First Check that given location is covered by Robosto
            $checkLocationCovered = (new CheckPointInArea($data['addressInfo']['location']))->check();
            if (!$checkLocationCovered) {
                // Save the address but
                $addressInfo['covered'] = '0';
                $this->customerAddressRepository->create($addressInfo);

                return $this->responseError(422, __('core::app.areaNotCovered'));
            }

            // Check Area is active
            $areaID = Area::find($checkLocationCovered);
            if ($areaID && $areaID->status == 0) {
                $addressInfo['covered'] = '0';
            }

            // Get Area Founded from the given location
            $data['addressInfo']['area_id'] = $checkLocationCovered;
            $addressInfo['area_id'] = $checkLocationCovered;

            $this->customerAddressRepository->create($addressInfo);
        }
    }

    public function update(Customer $customer, CustomerUpdateRequest $request) {
        $data = $request->only('name', 'gender', 'phone', 'landline', 'email', 'area_id');
        $data['channel_id'] = Channel::CALL_CENTER; // 1 = call center
        // Get Avatar Data
        $avatar = Avatar::where('gender', $data['gender'])->first();
        if (!$avatar) {
            $avatar = Avatar::first();
        }
        $data['avatar_id'] = $avatar->id;
        $data['avatar'] = $avatar->image;

        $before = deep_copy($customer);

        $customer = $this->customerRepository->update($data, $customer->id);

        Event::dispatch('admin.log.activity', ['update', 'customer', $customer, auth('admin')->user(), $customer, $before]);

        return $this->responseSuccess(null, "Customer has been updated!");
    }

    public function updateCustomerName(Customer $customer, CustomerUpdateNameRequest $request) {
        $data = $request->only('name');
        $before = deep_copy($customer);

        $customer = $this->customerRepository->update($data, $customer->id);

        Event::dispatch('admin.log.activity', ['update', 'customer', $customer, auth('admin')->user(), $customer, $before]);

        return $this->responseSuccess(null, "Customer has been updated!");
    }

    public function show(Customer $customer) {
        $data = new CustomerResource($customer);
        return $this->responseSuccess($data);
    }

    public function invitationsLogs(Customer $customer, Request $request) {
        $customer = $this->customerRepository->invitersList($customer, $request);

        $data = new CustomerInvitationAll($customer); // using customer repository
        return $this->responsePaginatedSuccess($data, null, $request);
        //  $data = new CustomerInvitationAll($customer->inviters());
        //return $this->responseSuccess($data, null);
    }


    public function updateCustomerWallet(Customer $customer, CustomerUpdateWalletRequest $request)
    {
        if (!auth('admin')->user()->hasRole([Role::SUPER_ADMIN])) {
            return $this->responseError(403, "You don't have permission to do this process");
        }

        $data = $request->only('text', 'amount', 'flag', 'order_id', 'reason_id', 'products');
        $data['admin_id'] = auth('admin')->id();
        $before = deep_copy($customer);

        Log::info("Start Update Wallet");
        Log::info(["Data" => $data]);

        $this->customerRepository->updateCustomerWallet($customer, $data);

        Event::dispatch('admin.log.activity', ['update', 'customer', $customer, auth('admin')->user(), $customer, $before]);

        return $this->responseSuccess(null, "Customer Wallet has been updated successfully");
    }

    public function callcenterCheckPhone(CallcenterCheckPhoneRequest $request) {
        $data = $request->only('phone');
        Event::dispatch('call.recieved.from.customer', $data);
        if (count($this->customerRepository->findWhere(['phone' => $data['phone']])) > 0) {
            $customer = $this->customerRepository->findOneWhere(['phone' => $data['phone']]);
            $append['newCustomer'] = false;
            $data = new CustomerResource($customer, $append); // using customer repository
            return $this->responseSuccess($data, "Customer exists!");
        }
        $data['channel_id'] = Channel::CALL_CENTER; // 1 = call center
        $customer = $this->customerRepository->create($data);

        // set settings for new customer
        SetCustomerSettings::dispatch($customer);

        // set (new-user) tag for new customer
        SetCustomerTag::dispatch($customer);

        Event::dispatch('admin.customer.created.by.callcenter', $customer);
        $append = ['newCustomer' => true];
        $data = new CustomerResource($customer, $append); // using customer repository
        return $this->responseSuccess($data, "New Customer has been created!");
    }

    public function callcenterUpdateProfile(Customer $customer, CallcenterProfileUpdateRequest $request) {
        $data = $request->only('name', 'gender', 'phone', 'landline', 'email', 'area_id', 'addressInfo');
        $data['channel_id'] = Channel::CALL_CENTER; // 1 = call center

        $before = deep_copy($customer);

        $customer = $this->customerRepository->update($data, $customer->id);

        if (count($data['addressInfo']) > 0) {
            $addressInfo = $data['addressInfo'];
            $addressInfo['customer_id'] = (int) $customer->id;
            $addressInfo['latitude'] = $data['addressInfo']['location']['lat'];
            $addressInfo['longitude'] = $data['addressInfo']['location']['lng'];

            // First Check that given location is covered by Robosto
            $checkLocationCovered = (new CheckPointInArea($data['addressInfo']['location']))->check();
            if (!$checkLocationCovered) {
                // Save the address but
                $addressInfo['covered'] = '0';
                $this->customerAddressRepository->create($addressInfo);

                return $this->responseError(422, __('core::app.areaNotCovered'));
            }

            if (!isset($data['addressInfo']['phone']) || empty($data['addressInfo']['phone'])) {
                $addressInfo['phone'] = $customer->phone;
            }

            // Check Area is active
            $areaID = Area::find($checkLocationCovered);
            if ($areaID && $areaID->status == 0) {
                $addressInfo['covered'] = '0';
            }
            // Get Area Founded from the given location
            $data['addressInfo']['area_id'] = $checkLocationCovered;

            $this->customerAddressRepository->create($addressInfo);
        }

        Event::dispatch('admin.customer.updatedProfile', $customer);
        Event::dispatch('admin.log.activity', ['update', 'customer', $customer, auth('admin')->user(), $customer, $before]);

        return $this->responseSuccess(null, 'Customer has been succussfully created!');
    }

    public function addressesList(Customer $customer) {
        return $this->responseSuccess($this->customerAddressRepository->findWhere(['customer_id' => $customer->id]));
    }

    public function addressShow($customerAddress) {
        return $this->responseSuccess($this->customerAddressRepository->findOrFail($customerAddress));
    }

    public function addressAdd(CustomerAddressCreateRequest $request) {
        $data = $request->only('customer_id', 'area_id', 'icon_id', 'name', 'address', 'building_no', 'floor_no', 'apartment_no', 'landmark', 'location', 'phone', 'is_default');


        $data['latitude'] = $data['location']['lat'];
        $data['longitude'] = $data['location']['lng'];
        $customer = Customer::findOrFail($data['customer_id']);

        if (!isset($data['phone']) || empty($data['phone'])) {
            $data['phone'] = $customer->phone;
        }

        // First Check that given location is covered by Robosto
        $checkLocationCovered = (new CheckPointInArea($data['location']))->check();
        if (!$checkLocationCovered) {
            // Save the address with not covered status
            $data['covered'] = '0';
            $this->customerAddressRepository->create($data);

            return $this->responseError(422, __('core::app.areaNotCovered'));
        }

        // Check Area is active
        $areaID = Area::find($checkLocationCovered);
        if ($areaID && $areaID->status == 0) {
            $data['covered'] = '0';
        }
        // Get Area Founded from the given location
        $data['area_id'] = $checkLocationCovered;
        $address = $this->customerAddressRepository->create($data);

        Event::dispatch('admin.log.activity', ['create-address', 'customer', $customer, auth($this->guard)->user(), $address]);

        return $this->responseSuccess(null, 'Customer Address has been succussfully created!');
    }

    public function addressUpdate(CustomerAddress $address, CustomerAddressUpdateRequest $request) {

        $data = $request->only('customer_id', 'area_id', 'icon_id', 'name', 'address', 'building_no', 'floor_no', 'apartment_no', 'landmark', 'location', 'phone', 'is_default');
        $data['latitude'] = $data['location']['lat'];
        $data['longitude'] = $data['location']['lng'];
        $customer = Customer::findOrFail($data['customer_id']);
        $before = deep_copy($address);

        if (!isset($data['phone']) || empty($data['phone'])) {
            $data['phone'] = $customer->phone;
        }

        // First Check that given location is covered by Robosto
        $checkLocationCovered = (new CheckPointInArea($data['location']))->check();
        if (!$checkLocationCovered) {
            // Save the address with not covered status
            $data['covered'] = '0';
            $this->customerAddressRepository->create($data);

            return $this->responseError(422, __('core::app.areaNotCovered'));
        }

        // Check Area is active
        $areaID = Area::find($checkLocationCovered);
        if ($areaID && $areaID->status == 0) {
            $data['covered'] = '0';
        }

        // Get Area Founded from the given location
        $data['area_id'] = $checkLocationCovered;
        $addressUpdated = $this->customerAddressRepository->update($data, $address->id);

        Event::dispatch('admin.log.activity', ['update-address', 'customer', $customer, auth($this->guard)->user(), $addressUpdated, $before]);

        return $this->responseSuccess(null, 'Customer Address has been succussfully updated!');
    }

    /**
     * @param CustomerAddress $address
     *
     * @return mixed
     */
    public function addressDelete(CustomerAddress $address) {
        $customer = Customer::find($address->customer_id);

        $this->customerAddressRepository->delete($address->id);

        Event::dispatch('admin.log.activity', ['delete-address', 'customer', $customer, auth($this->guard)->user(), $address]);

        return $this->responseSuccess(null, 'Customer Address has been succussfully Deleted!');
    }

    public function orders(Customer $customer, Request $request) {
        $ordesList = $this->customerRepository->ordersList($customer, $request);
        $orders = new \Webkul\Admin\Http\Resources\Sales\OrderAll($ordesList);
        return $this->responsePaginatedSuccess($orders, null, $request);
    }

    /**
     * @param OrderModel $order
     * @return JsonResponse
     */
    public function noteCreate(Request $request) {
        $data = $request->only(['text', 'customer_id']);
        $customer = $this->customerRepository->findOrFail($data['customer_id']);

        $customer->customerNotes()->create([
            'text' => $data['text'],
            'customer_id' => $data['customer_id'],
            'admin_id' => auth($this->guard)->user()->id,
        ]);

        return $this->responseSuccess();
    }

    public function noteList(Request $request) {
        $data = $request->only(['customer_id']);
        $customer = $this->customerRepository->findOrFail($data['customer_id']);
        $notes = new CustomerNotesAll($customer->customerNotes()->get());
        return $this->responseSuccess($notes);
    }

    public function walletReasonList(Request $request) {
        $data = $request->only(['flag']);
        if ($data['flag'] == 'plus') {
            $where = ['is_added' => 1];
        } else {
            $where = ['is_reduced' => 1];
        }
        $reasons = WalletCustomerReason::where($where)->get(['id', 'reason', 'type']);

        return $this->responseSuccess($reasons);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function createGroup(CreateGroupRequest $request) {
        $data = $request->only(['name', 'customers', 'tags']);

        // Create New Tag
        $newTag = Tag::create([
                    'name' => $data['name']
        ]);

        // // Append New Tag to the customers
        if (isset($data['customers']) && !empty($data['customers'])) {
            $newTag->customers()->attach($data['customers']);
        }

        // Also Append Old Tag to the customers
        if (isset($data['tags']) && !empty($data['tags'])) {
            foreach ($data['tags'] as $tag) {

                $oldTag = Tag::find($tag);
                $oldTag->customers()->syncWithoutDetaching($data['customers']);
            }
        }

        return $this->responseSuccess($newTag);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateGroup(Request $request) {
        $data = $request->only(['customer_id', 'tags']);

        $customer = $this->customerRepository->findOrFail($data['customer_id']);

        // Also Append Old Tag to the customers
        if (isset($data['tags']) && !empty($data['tags'])) {

            $customer->tags()->sync($data['tags']);

            // Handle Tag Send SMS
            SendTagSMS::dispatch($customer, $data['tags']);
        }


        return $this->responseSuccess();
    }

    /**
     * @param Collection $customers
     * @param array $tags
     *
     * @return void
     */
    public function retentionMessage(Collection $customers, array $tags) {
        Log::info('looping and sending messages');
        foreach ($customers as $customer) {
            Log::info('start sending to customer');
            Log::info($customer);
            $customer->tags()->syncWithoutDetaching($tags);
            $this->sendTagSms($customer, $tags);
            Log::info('done sending to customer');
            Log::info($customer);
        }
    }

    /**
     * @param Customer $customer
     * @param Request $request
     *
     * @return void
     */
    public function sendTagSms(Customer $customer, array $tags) {
        if (count($tags)) {
            foreach ($tags as $tag) {
                // Get the Tag
                $tagModel = Tag::find($tag);
                // in case of the tag has Send SMS
                if ($tagModel->send_sms) {
                    // Get First Promotion for this Tag
                    $tagPromotion = $tagModel->promotions->first();

                    if ($tagPromotion && $tagPromotion->sms_content) {
                        // Handle SMS Content
                        $text = replaceHashtagInText($tagPromotion->sms_content, $customer);

                        // Start Send SMS
                        $this->sendSMS($customer->phone, $text);
                    }
                }
            }
        }
    }

    /**
     * @param Promotion $promotion
     * @param Customer $customer
     *
     * @return void
     */
    public function handleOneOrderTag(Promotion $promotion, Customer $customer) {
        $tags = $promotion->tags;
        foreach ($tags as $tag) {
            // in case of this tag valid for one order, then detach from this customer
            if ($tag->one_order) {
                $customer->tags()->detach($tag->id);
            }
            // Update retention Customers if this tag has retention
            $this->updateRetentionCustomers($customer->id, $tag->id);
        }
    }

    /**
     * @param int $customerId
     * @param int $tagId
     *
     * @return void
     */
    private function updateRetentionCustomers(int $customerId, int $tagId) {
        $retentionTag = RetentionMessage::where('tag_id', $tagId)->first();
        if ($retentionTag) {
            $retentionedCustomers = $retentionTag->retentionedCustomers->where('customer_id', $customerId)->first();
            if ($retentionedCustomers) {
                $retentionedCustomers->update(['used' => 1]);
            }
        }
    }

    /**
     * @param RetentionMessage $retentionMessage
     *
     * @return bool
     */
    public function sendToNotifiers(RetentionMessage $retentionMessage) {
        $notifiers = $this->getNotifiers($retentionMessage);

        // if no notifiers, then delete old and was sent
        if ($notifiers->isEmpty()) {
            $this->purgeOldNotifiers($retentionMessage);
            return true;
        }


        $customers = Customer::whereIn('id', $notifiers->pluck('customer_id')->toArray())->get();

        // Send SMS and make some processing
        $this->retentionMessage($customers, [$retentionMessage->tag_id]);
        Log::info('updating notification');
        // Update Notifiers to SENT
        $customersWasSent = $notifiers->pluck('customer_id')->toArray();
        $this->updateNotifiers($retentionMessage, $customersWasSent);
        Log::info($retentionMessage);
        Log::info($customersWasSent);
        RetentionsNotifier::dispatch($retentionMessage);
        return true;
    }

    /**
     * @param RetentionMessage $retentionMessage
     *
     * @return Collection
     */
    public function getNotifiers(RetentionMessage $retentionMessage) {
        return Notifier::where('entity_type', Notifier::RETENTION_TYPE)
                        ->where('entity_id', $retentionMessage->id)
                        ->where('sent', 0)
                        ->limit(20)
                        ->get();
    }

    /**
     * @param RetentionMessage $retentionMessage
     *
     * @return void
     */
    public function purgeOldNotifiers(RetentionMessage $retentionMessage) {
        Notifier::where('entity_type', Notifier::RETENTION_TYPE)
                ->where('entity_id', $retentionMessage->id)
                ->delete();
    }

    /**
     * @param RetentionMessage $retentionMessage
     * @param array $customer
     *
     * @return void
     */
    public function updateNotifiers(RetentionMessage $retentionMessage, $customers) {
        Notifier::where('entity_type', Notifier::RETENTION_TYPE)
                ->where('entity_id', $retentionMessage->id)
                ->whereIn('customer_id', $customers)
                ->update(['sent' => 1]);
    }

    public function paymentCardsList(Customer $customer) {
        $cards = $customer->paymobCards()->where('status', '1')->get();
        return $this->responseSuccess($cards);
    }

    public function deletePaymentCard(PaymobCard $card) {
        $card->update(['status' => '0']);
        return $this->responseSuccess();
    }

    // List Customer Devices
    public function devicesList(Request $request) {
        $data = $request->only(['customer_id']);
        $orderWithDevices=$this->customerRepository->devicesList($request);
        $devices = new CustomerDevicesAll($orderWithDevices);
        return $this->responsePaginatedSuccess($devices, null, $request);        
    }



    /**
     * @param Customer $customer
     * 
     * @return JsonResponse
     */
    public function callWithOtp(Customer $customer) {
        if (!$customer->latestLoginOtps) {
            return $this->responseSuccess(null, "من فضلك اطلب من العميل انشاء كود جديد");
        }

        // If OTP Expired
        if ($customer->latestLoginOtps->expired_at < now()) {
            return $this->responseSuccess(null, "من فضلك اطلب من العميل انشاء كود جديد");
        }

        $otp = $customer->latestLoginOtps->otp;

        // Call The Customer with his OTP
        (new CallCustomer)->callCustomerWithOtp($customer->phone, $otp);

        return $this->responseSuccess(null, "شكراً. تم ارسال مكالمه للعميل تحتوى  على الكود الخاص به");
    }

    /**
     * @param Customer $customer
     * 
     * @return JsonResponse
     */
    public function sendSmsToCustomer(Customer $customer, Request $request) {
        $this->validate($request, [
            'text' => 'required|string'
        ]);
        $data = $request->only('text');

        $response = $this->sendSMS($customer->phone, $data['text']);

        if ($response != 0) {
            return $this->responseError(410, "The message was not sent successfully, please try again");
        }

        $msg = "Mobile SMS : ";
        $customer->customerNotes()->create([
            'text' => $msg . $data['text'],
            'admin_id' => auth('admin')->user()->id,
        ]);

        return $this->responseSuccess(null, "شكراً. تم ارسال الرسالة للعميل");
    }
}
