<?php

namespace Webkul\Customer\Models;

use Carbon\Carbon;
use Laravel\Scout\Searchable;
use Webkul\Sales\Models\Order;
use Webkul\Core\Models\TagProxy;
use Webkul\Sales\Models\OrderProxy;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Checkout\Models\CartProxy;
use Webkul\Customer\Events\MoneyAdded;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Webkul\Product\Models\ProductProxy;
use Illuminate\Notifications\Notifiable;
use Webkul\Core\Models\ActivityLogProxy;
use Webkul\Customer\Events\MoneySubtracted;
use Webkul\Customer\Models\PaymobCardProxy;
use Webkul\Customer\Events\WalletMoneyAdded;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Product\Models\ProductReviewProxy;
use Webkul\Customer\Events\InvitationMoneyAdded;
use Webkul\Customer\Models\CustomerPaymentProxy;
use Webkul\Customer\Events\WalletMoneySubtracted;
use Webkul\Promotion\Models\PromotionRedeemProxy;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Webkul\Customer\Contracts\Customer as CustomerContract;
use Webkul\Customer\Events\BNPLMoneySubtracted;
use Webkul\Customer\Events\MoneyAddedFromPromotionCashback;
use Webkul\Promotion\Models\PromotionVoidDeviceProxy;
use Webkul\Promotion\Models\PromotionVoidDevice;
use Webkul\Customer\Models\CustomerDeviceProxy;


class Customer extends Authenticatable implements CustomerContract, JWTSubject {

    use Notifiable,
        Searchable,
        SoftDeletes;

    public $asYouType = true;
    protected $table = 'customers';
    protected $fillable = [
        'channel_id',
        'avatar_id',
        'avatar',
        'name',
        'email',
        'gender', // 0 male 1 female
        'date_of_birth',
        'phone',
        'landline',
        'notes',
        'wallet',
        'status',
        'is_online',
        'otp_verified',
        'invitation_applied',
        'invited_by',
        'total_orders',
        'delivered_orders',
        'credit_wallet',
        'is_flagged',
        'subscribed_to_news_letter',
    ];
    protected $hidden = ['password'];
    protected $appends = ['avatar_url'];


   // protected $casts=['gender'=>'boolean','status'=>'boolean','otp_verified'=>'boolean'];

    public function searchableAs() {
        return 'customers';
    }

    public function toSearchableArray() {
        // $array['ngrams'] = utf8_encode((new TNTIndexer)->buildTrigrams($this->email));
        return [
            'id' => $this->id,
//            'nameGrams'=>  (new TNTIndexer)->buildTrigrams($this->name) ,
//            'name2Grams'=>  $this->buildTrigrams($this->name) ,
//            'emailGrams'=> utf8_encode((new TNTIndexer)->buildTrigrams($this->email)),
//            'email2Grams'=>  utf8_encode($this->buildTrigrams($this->email)),
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'landline' => $this->landline,
                //'genderGrams'=> $this->gender==0?'Male':'Female',
                //'gender' => $this->gender ,
        ];
    }

    /**
     * Phone exists or not
     */
    public function phoneExists($phone) {
        $results = $this->where('phone', $phone);

        if ($results->count() == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get all Logs
     */
    public function logs() {
        return $this->morphMany(ActivityLogProxy::modelClass(), 'subject');
    }

    public function channel() {
        return $this->belongsTo(ChannelProxy::modelClass());
    }

    public function myAvatar() {
        return $this->belongsTo(AvatarProxy::modelClass(), 'avatar_id');
    }

    /**
     * Get the login otps that owns the customer.
     */
    public function loginOtps() {
        return $this->hasMany(CustomerLoginOtpProxy::modelClass(), 'customer_id');
    }

    /**
     * Get the login otps that owns the customer.
     */
    public function latestLoginOtps() {
        return $this->hasOne(CustomerLoginOtpProxy::modelClass(), 'customer_id')->latest();
    }

    /**
     * Get the customer address that owns the customer.
     */
    public function addresses() {
        return $this->hasMany(CustomerAddressProxy::modelClass(), 'customer_id');
    }

    public function deviceToken() {
        return $this->hasMany(CustomerDeviceTokenProxy::modelClass());
    }

    /**
     * Get the invitations for the customer.
     */
    public function invitationsLogs() {
        return $this->hasMany(CustomerInvitationProxy::modelClass(), 'customer_id');
    }

    /**
     * Get the invitated by who owns the customer.
     */
    public function invitedBy() {
        return $this->belongsTo(CustomerProxy::modelClass(), 'invited_by');
    }

    /**
     * Get the invitated by who owns the customer.
     */
    public function inviters() {
        return Customer::where('invited_by', $this->id)->get();
    }


    /**
     * Get default customer address that owns the customer.
     */
    public function default_address() {
        return $this->hasOne(CustomerAddressProxy::modelClass(), 'customer_id')->where('is_default', 1);
    }

    /**
     * Get default customer address that owns the customer.
     */
    public function getDefaultAddressID() {
        $customerOrders = $this->orders;
        if ($customerOrders->isNotEmpty()) {
            return $customerOrders->last()->address_id;
        }

        if (!count($this->addresses)) {
            return null;
        }

        return $this->addresses->last()->id;
    }

    public function default_area() {
        return $this->hasOne(CustomerAddressProxy::modelClass(), 'customer_id')->where('is_default', 1);
    }

    /**
     * Customer's relation with wishlist items
     */
    public function wishlist_items() {
        return $this->hasMany(WishlistProxy::modelClass(), 'customer_id');
    }

    /**
     * get all reviews of a customer
     */
    public function all_reviews() {
        return $this->hasMany(ProductReviewProxy::modelClass(), 'customer_id');
    }

    /**
     * get all orders of a customer
     */
    public function all_orders() {
        return $this->hasMany(OrderProxy::modelClass(), 'customer_id');
    }

    public function orders() {
        return $this->hasMany(OrderProxy::modelClass(), 'customer_id');
    }

    public function pendingOrders() {
        return $this->orders()->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_WAITING_CUSTOMER_RESPONSE])->latest();
    }

    /**
     * get active orders of a customer
     * where not cancelled (0) or delivered (5)
     */
    public function activeOrders() {
        return $this->hasMany(OrderProxy::modelClass(), 'customer_id')
                        ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_CANCELLED_FOR_ITEMS, Order::STATUS_DELIVERED, Order::STATUS_RETURNED])->latest();
    }

    public function previousOrders() {
        return $this->hasMany(OrderProxy::modelClass(), 'customer_id')
                        ->whereIn('status', [Order::STATUS_CANCELLED, Order::STATUS_DELIVERED])->latest();
    }

    public function products() {
        return $this->belongsToMany(ProductProxy::modelClass(), 'customer_products', 'customer_id', 'product_id');
    }

    public function customerNotes() {
        return $this->hasMany(CustomerNoteProxy::modelClass());
    }

    public function walletNotes() {
        return $this->hasMany(WalletNoteProxy::modelClass());
    }

    /**
     * @return [type]
     */
    public function vapulusCards() {
        return $this->hasMany(VapulusCardProxy::modelClass(), 'customer_id');
    }

    /**
     * @return [type]
     */
    public function paymobCards() {
        return $this->hasMany(PaymobCardProxy::modelClass(), 'customer_id');
    }

    public function payments($param) {
        return $this->hasMany(CustomerPaymentProxy::modelClass(), 'customer_id');
    }


    public function devices() {
        return $this->hasMany(CustomerDeviceProxy::modelClass(), 'customer_id');
    }
    
    public function uniqueDevices() {
        $devices = $this->devices;

        $uniqueDevices = $devices->unique('deviceid');
        foreach ($uniqueDevices as $k => $device) {
            // get customer acoutns that use selected device 
            $customerDevices = $device->myAccounts();
           
            $uniqueCustomerDevices = $customerDevices->unique('customer_id','deviceid');
            foreach ($uniqueCustomerDevices as $row) {
                $uniqueDevices->push($row);
            }
        }

        return $uniqueDevices->unique('deviceid');
    }

    public function allAccounts() {
        $devices = $this->devices;

        $uniqueDevices = $devices->unique('deviceid');
        foreach ($uniqueDevices as $k => $device) {
            // get customer acoutns that use selected device 
            $customerDevices = $device->myAccounts();
           
            $uniqueCustomerDevices = $customerDevices->unique('customer_id','deviceid');
            foreach ($uniqueCustomerDevices as $row) {
                $uniqueDevices->push($row);
            }
        }

        return $uniqueDevices->unique('customer_id');
    }    

    /**
     * @param float $amount
     * @param int $inviterId
     *
     * @return mixed
     */
    public function addInvitaionMoney(float $amount, int $inviterId) {
        event(new InvitationMoneyAdded($this->id, $amount, $inviterId));
    }

    /**
     * @param float $amount
     * @param int|null $orderId
     * @param int|null $orderIncrementId
     *
     * @return [type]
     */
    public function addMoney(float $amount, int $orderId = null, int $orderIncrementId = null) {
        event(new MoneyAdded($this->id, $amount, $orderId, $orderIncrementId));
    }

    /**
     * @param float $amount
     * @param int|null $orderId
     * @param int|null $orderIncrementId
     *
     * @return [type]
     */
    public function subtractMoney(float $amount, int $orderId = null, int $orderIncrementId = null) {
        event(new MoneySubtracted($this->id, $amount, $orderId, $orderIncrementId));
    }

    /**
     * @param int $adminId
     * @param float $amount
     * @param string $note
     * 
     * @return mixed
     */
    public function addMoneyToWallet(int $adminId, float $amount, string $note) {
        event(new WalletMoneyAdded($this->id, $adminId, $amount, $note));
    }

    /**
     * @param int $adminId
     * @param float $amount
     * @param string $note
     * 
     * @return mixed
     */
    public function subtractMoneyFromWallet(int $adminId, float $amount, string $note) {
        event(new WalletMoneySubtracted($this->id, $adminId, $amount, $note));
    }

    public function subtractBNPLMoney(float $amount, int $orderId = null) {
        event(new BNPLMoneySubtracted($this->id,  $amount, $orderId));
    }

    public function addMoneyFromPromotionCashback(float $amount, int $orderId = null, int $promotionId = null) {
        event(new MoneyAddedFromPromotionCashback($this->id, $amount, $orderId, $promotionId));
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier() {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims() {
        return [];
    }

    /**
     * Get image url for the category image.
     */
    public function getAvatarUrlAttribute() {
        if (!$this->avatar) {
            return null;
        }
        return Storage::url($this->avatar);
    }

    /**
     * Get image url for the category image.
     */
    public function handleDefaultAvatar() {
        return Avatar::where('gender', (string) $this->gender)->first();
    }

    public function buildTrigrams($keyword) {
        $t = "  " . $keyword . "  ";
        $trigrams = "";
        for ($i = 0; $i < strlen($t) - 2; $i++) {
            for ($k = 1; $k < 8; $k++) {
                $trigrams .= mb_substr($t, $i, $k) . " ";
            }
        }

        return trim($trigrams);
    }

    public function tags() {
        return $this->belongsToMany(TagProxy::modelClass(), 'customer_tags')->withTimestamps();
    }

    public function promotionRedeems() {
        return $this->hasMany(PromotionRedeemProxy::modelClass());
    }

    public function settings() {
        return $this->hasMany(CustomerSettingProxy::modelClass());
    }

    public function BNPLTransactions() {
        return $this->hasMany(BuyNowPayLaterProxy::modelClass());
    }
    public function calculatedCreditWallet(){
        $ordersINBNPLConditions = $this->orders->where('status', Order::STATUS_DELIVERED)->where('created_at','>',Carbon::now()->subMonths(config('robosto.BNPL_AFTER_MONTH'))->toDateString());
        $ordersCount = $ordersINBNPLConditions->count();
        $ordersTotal = $ordersINBNPLConditions->sum('final_total');
        if($ordersCount==0){
            $maxAmountToUse = 0;
        }else{
            $maxAmountToUse = $ordersCount / 10 *  $ordersTotal / $ordersCount;
        }
        $total =  $maxAmountToUse - $this->credit_wallet < 0 ? 0 : $maxAmountToUse - $this->credit_wallet;
        return ["remaining_to_use"=>(int) ceil($total) , "credit"=>$this->credit_wallet , "circle_color"=>"#ffce1c" , "info"=>"Buy Now & Pay Later\nYou can now buy your favorite products from Robosto, and pay later.\nWhen you did 5 orders in the last 2 months, you will get a credit amount in your account to buy now and pay later."];
    }
    public function favoriteProducts() {
        return $this->belongsToMany(ProductProxy::modelClass(), 'favorite_customers_products')->wherePivot('favorite',1);
    }
}
