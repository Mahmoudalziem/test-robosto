<?php

namespace Webkul\Customer\Repositories;

use Illuminate\Support\Str;
use Webkul\Core\Models\Tag;
use App\Jobs\SetCustomerTag;
use App\Jobs\SetCustomerSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Eloquent\Repository;
use Webkul\Customer\Models\Customer;
use Illuminate\Support\Facades\Event;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Container\Container as App;
use Webkul\Customer\Models\VapulusCard;
use Webkul\Sales\Models\Order;

class CustomerRepository extends Repository
{

    protected $customerAddressRepository;
    public function __construct(
        CustomerAddressRepository $customerAddressRepository,
        App $app
    ) {
        $this->customerAddressRepository = $customerAddressRepository;
        parent::__construct($app);
    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */

    function model()
    {
        return 'Webkul\Customer\Contracts\Customer';
    }

    public function add($data)
    {
        $data['is_verified'] = 1;
        $data['status'] = 1;
        $data['channel_id'] = 2; // channel 1 => default ( customer mobile app(ecomm)) , 2 => callcenter
        $customer = $this->create($data);
        Event::dispatch('customer.create.after', $customer);

        return $customer;
    }

    public function create($data)
    {
        // Get Avatar Data
        $avatar = $this->getAvatarData($data['avatar_id']);
        $data['avatar_id'] = $avatar->id;
        $data['gender'] = $avatar->gender;
        $data['avatar'] = $avatar->image;

        $customer = $this->model->create($data);

        // set settings for new customer
        SetCustomerSettings::dispatch($customer);

        // set (new-user) tag for new customer
        SetCustomerTag::dispatch($customer);

        Event::dispatch('customer.create.after', $customer);

        return $customer;
    }

    public function update($data, $customer)
    {
        // Get Avatar Data
        $avatar = $this->getAvatarData($data['avatar_id']);
        $data['avatar_id'] = $avatar->id;
        $data['gender'] = $avatar->gender;
        $data['avatar'] = $avatar->image;

        $customer = $customer->update($data);

        return $customer;
    }

    public function deleteAccount($customer)
    {
        $customer = $customer->update(['status'=>0]);
        return $customer;
    }

    public function list($request)
    {
        $query = $this->newQuery();

        //$query = app(App\User::class)->newQuery()->with('group');

        // handle sort option
        if ($request->has('sort') && !empty($request->sort)) {

            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'asc');
        }

        if ($request->exists('filter')) {
            $query->where(function ($q) use ($request) {
                $value = "%{$request->filter}%";
                $q->where('name', 'like', $value)
                    ->orWhere('email', 'like', $value);
            });
        }

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;

        $pagination = $query->with('addresses')->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }

    public function show($id)
    {
        return $this->find($id);
    }

    public function addAddress($data)
    {

        $customer = $this->find($data['customer_id']);
        $data['email'] = $customer['email'];
        $data['phone'] = $customer['phone'];
        $data['state'] = 'Cairo';
        $data['city'] = 'Cairo';
        $data['country'] = 'Egypt';
        $data['longitude'] = '0.0';
        $data['latitude'] = '0.0';
        $data['default_address'] = 1;
        return $customer->addresses()->create($data);
    }

    public function addressesList($customer)
    {
        $customer = $this->customerAddressRepository->findWhere(['customer_id' => $customer->id]);
        return $customer;
    }



    /**
     * Check if customer has order pending or processing.
     *
     * @param Webkul\Customer\Models\Customer
     * @return boolean
     */
    public function checkIfCustomerHasOrderPendingOrProcessing($customer)
    {
        return $customer->all_orders->pluck('status')->contains(function ($val) {
            return $val === 'pending' || $val === 'processing';
        });
    }

    /**
     * Check if bulk customers, if they have order pending or processing.
     *
     * @param array
     * @return boolean
     */
    public function checkBulkCustomerIfTheyHaveOrderPendingOrProcessing($customerIds)
    {
        foreach ($customerIds as $customerId) {
            $customer = $this->findorFail($customerId);

            if ($this->checkIfCustomerHasOrderPendingOrProcessing($customer)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $avatar_id
     * @return Model|Builder|object|null
     */
    private function getAvatarData($avatar_id)
    {
        return DB::table('avatars')->where('id', $avatar_id)->first();
    }

    /**
     * @param $customer
     * @param $token
     */
    public function storeDeviceToken($customer, $data)
    {
        // if device info provided
        if (isset($data['device_id']) && !empty($data['device_id'])) {
            // Get Old Device
            $oldDevice = $customer->deviceToken()->where('device_id', $data['device_id'])->first();

            if ($oldDevice) {

                // Restore new token                
                $oldDevice->token = $data['device_token'];
                $oldDevice->save();
            } else {

                // delete Empty Old tokens
                DB::table('customer_device_tokens')->where('customer_id', $customer->id)->whereNull('device_id')->delete();

                // Save new Device
                $customer->deviceToken()->create([
                    'token'   =>  $data['device_token'],
                    'device_id'   =>  $data['device_id'],
                    'device_type'   =>  $data['device_type'],
                ]);
            }

            return true;
        }

        // if no device info provided
        DB::table('customer_device_tokens')->where('customer_id', $customer->id)->delete();
        $customer->deviceToken()->create(['token'   =>  $data['device_token']]);
    }

    /**
     * @param Customer $customer
     * 
     * @return [type]
     */
    public function setCustomerTag(Customer $customer)
    {
        // in case: New Customer Registered
        if ($customer->orders->where('status', Order::STATUS_DELIVERED)->count() == 0) { // after register order
            $customer->tags()->attach([Tag::NEW_USER, Tag::ALL_USERS]);
        }

        // in case: Complete First Order
        if ($customer->orders()->where('status', Order::STATUS_DELIVERED)->count() == 1) {
            $customer->tags()->detach(Tag::NEW_USER);
            $customer->tags()->syncWithoutDetaching([Tag::FIRST_ORDER, Tag::ALL_USERS]);
        }

        // in case: Complete Second Order
        if ($customer->orders()->where('status', Order::STATUS_DELIVERED)->count() == 2) {
            $customer->tags()->detach(Tag::FIRST_ORDER);
            $customer->tags()->syncWithoutDetaching([Tag::SECOND_ORDER, Tag::ALL_USERS]);
        }
        
        // in case: Complete Third Order
        if ($customer->orders()->where('status', Order::STATUS_DELIVERED)->count() == 3) {
            $customer->tags()->detach(Tag::SECOND_ORDER);
        }
    }


    /**
     * @param Customer $customer
     * @param int $promotionID
     * 
     * @return [type]
     */
    public function updateCustomerPromotionRedeems(Customer $customer, int $promotionID)
    {
        $customerRedeems = $customer->promotionRedeems;

        if ($customerRedeems) {

            $customerRedeem = $customerRedeems->where('promotion_id', $promotionID)->first();
            if ($customerRedeem) {
                $customerRedeem->redeems_count += 1;
                $customerRedeem->save();

            } else {
                
                $customer->promotionRedeems()->create([
                    'redeems_count' =>  1,
                    'promotion_id'  =>  $promotionID
                ]);
            }
        } else {

            $customer->promotionRedeems()->create([
                'redeems_count' =>  1,
                'promotion_id'  =>  $promotionID
            ]);
        }
    }


    /**
     * @param Customer $customer
     * @param int $promotionID
     * 
     * @return [type]
     */
    public function updateCustomerPromotionRedeemsIfOrderCancelled(Customer $customer, int $promotionID)
    {
        $customerRedeems = $customer->promotionRedeems->where('promotion_id', $promotionID)->first();

        if ($customerRedeems) {
            // Increase Redeems count again
            $customerRedeems->redeems_count -= 1;
            $customerRedeems->save();
        }
    }

    public function setCustomerSettings($customer)
    {
        $settings =
            [
                ['key' => 'lang', 'value' =>   'ar',  'group' => 'lang'],
                ['key' => 'app_notification', 'value' =>      1,  'group' => 'notification'],
                ['key' => 'email_notification', 'value' =>      1,  'group' => 'notification'],
                ['key' => 'sms_notification', 'value' =>      1,  'group' => 'notification'],
            ];
        foreach ($settings as $setting) {
            $customer->settings()->create($setting);
        }
        return;
    }


    /**
     * handle Invitaion Code
     *
     * @array $data
     * @var $newCustomer
     */
    public function handleInvitaionCode($newCustomer, array $data)
    {
        // handle Invitaion Code if the customer provide referral code
        if (isset($data['referral_code']) && !empty($data['referral_code'])) {

            $referralCode = $data['referral_code'];

            // Get Customer who has the code
            $ownerCustomer = $this->findOneWhere(['referral_code'    =>  $referralCode]);

            if (!$ownerCustomer) {
                return true;
            }

            // Update New Customer
            $newCustomer->invitation_applied = 0;
            $newCustomer->invited_by = $ownerCustomer->id;
            $newCustomer->save();
        }

        return true;
    }


    /**
     * @param Customer $invited
     * @param Order $order
     * 
     * @return bool
     */
    public function addMoneyToReferralCodeOwner(Customer $invited, Order $order)
    {
        // Update This customer to invitation applied
        $invited->invitation_applied = 1;
        $invited->save();

        // get Invite code Owner
        $invitationOwner = $invited->invitedBy;
        if ($invitationOwner) {
            // Add Money to Owner the Referral Code
            $invitationOwner->addInvitaionMoney((float) config('robosto.INVITE_CODE_GIFT'), $invited->id);

            // Save this gift in invitation logs
            $invitationOwner->invitationsLogs()->create([
                'inviter_id'    =>  $invited->id,
                'order_id'      =>  $order->id,
                'wallet'        =>  config('robosto.INVITE_CODE_GIFT'),
            ]);
        }

        return true;
    }

    /**
     * @param Customer $invited
     * @param Order $order
     * 
     * @return bool
     */
    public function addMoneyToReferralCodeUserOnFirstOrder(Customer $invited, Order $order)
    {
        $percentage = config('robosto.ORDER_INVITE_CODE_GIFT'); // 25%
        $money = 0;
        if ($order->final_total > 0) {
            $money = ($percentage / 100) * $order->final_total;     // (25/100) * 500   = 125 L.E
        }

        // Add a Percentage from the Order to New Customer who Used Referral Code
        $invited->addMoney($money, $order->id, $order->increment_id);

        return true;
    }

    /**
     * Generate New Referral Code
     */
    public function generateReferralCode($customer)
    {
        $name = $customer->name;
        $referralCode = $this->getUniqueReferralCode($name);
        // Save Code for this customer
        $customer->referral_code = $referralCode;
        $customer->save();

        return true;
    }


    private function getUniqueReferralCode($name){
        $getNameFormatted = Str::slug(explode(' ', $name)[0]);

        $randomNumber = rand(1000, 9999);

        $referralCode = $getNameFormatted . $randomNumber;

        // check if this referral code exist with another customer
        if ($this->findOneWhere(['referral_code'    =>  $referralCode])) {
           return $this->getUniqueReferralCode($name);
        }
        return $referralCode;
    }



    /**
     * Create New Card for this Customer
     * 
     * @param Customer $customer
     * @param array $data
     * 
     * @return [type]
     */
    public function createNewCard(Customer $customer, array $data)
    {
        $lastCardDigit = substr($data['card_number'], -4);
        $cardType = substr($data['card_number'], 0, 1) == 4 ? VapulusCard::VISA_TYPE : VapulusCard::MASTERCARD_TYPE;

        // Save the customer Card
        $customer->vapulusCards()->create([
            'last_digits'   =>  $lastCardDigit,
            'card_id'   =>  $data['card_id'],
            'user_id'   =>  $data['user_id'],
            'type'   =>  $cardType
        ]);

        return true;
    }
}
