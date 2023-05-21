<?php

namespace Webkul\Customer\Http\Controllers\Auth;

use App\Jobs\SetCustomerTag;
use Webkul\Core\Models\Channel;
use App\Enums\TrackingUserEvents;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\Customer;
use Illuminate\Support\Facades\Event;
use App\Jobs\NewCustomerInvitationCode;
use Prettus\Validator\Exceptions\ValidatorException;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Customer\Services\SMS\VictoryLink\SendSMS;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Customer\Http\Requests\CustomerRegisterRequest;

class RegisterController extends BackendBaseController
{

    use SMSTrait;

    /**
     * CustomerRepository object
     *
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * Create a new Repository instance.
     *
     * @param CustomerRepository $customerRepository
     */
    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * Method to store user's sign up form data to DB.
     *
     * @param CustomerRegisterRequest $request
     * @return JsonResponse
     * @throws ValidatorException
     */
    public function register(CustomerRegisterRequest $request)
    {
        // Collect Data
        $data = $request->only(['phone', 'name', 'email', 'avatar_id', 'referral_code']);
        $data['channel_id'] = Channel::MOBILE_APP;

        Log::info(["data" => $data]);

        if (isset($data['referral_code']) && !empty($data['referral_code'])) {
            $customer = $this->customerRepository->findOneByField('referral_code', $data['referral_code']);

            if (!$customer || count(Customer::where('invited_by', $customer->id)->get()) > config('robosto.REFERRAL_CODE_USAGE_COUNT')) {
                unset($data["referral_code"]);
            }
        }

        if (isset($data['email']) && !empty($data['email'])) {
            $customer = $this->customerRepository->findOneByField('email', $data['email']);

            if ($customer) {
                unset($data["email"]);
            }
        }

        // First Check if this phone Exist or Not
        $customerIsExist = $this->customerRepository->findOneByField('phone', $data['phone']);

        // If Customer Exist
        if ($customerIsExist) {
            return $this->responseError(202, __('customer::app.phoneNumberExist'));
        }

        // Create Customer
        $customer = $this->customerRepository->create($data);

        // Create New OTP for this Customer
        $newOTP = $this->createAndStoreOtp($customer);

        // Send OTP via SMS
        $text = __('customer::app.otpMessage', ['otp' => $newOTP]);
        $this->sendSMS($data['phone'], $text);

        // Generate Referral Code for this customer
        $this->customerRepository->generateReferralCode($customer);

        // Handle Referral Code
        NewCustomerInvitationCode::dispatch($customer, $data);
        // Set Default Tags for a new customer
        SetCustomerTag::dispatch($customer);

        // Fire Registration Event
        Event::dispatch('customer.register', $customer);
        //Event::dispatch('tracking.user.event', [TrackingUserEvents::COMPLETE_REGISTRATION, $customer, ['request' => $request]]);

        return $this->responseSuccess(null, __('customer::app.otpSent'));
    }

    /**
     * Create and Store New OTP
     *
     * @return int otp
     */
    private function createAndStoreOtp($customer)
    {
        $customerOtp = random_int(1000, 9999);
        $customer->loginOtps()->create([
            'otp' => $customerOtp,
            'expired_at' => now()->addMinutes(5)
        ]);

        return $customerOtp;
    }
}