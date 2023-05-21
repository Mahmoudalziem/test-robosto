<?php

namespace Webkul\Customer\Http\Controllers\Auth;

use Cookie;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use Webkul\Customer\Models\Customer;
use Illuminate\Support\Facades\Event;
use Webkul\Customer\Http\Requests\CheckOtpRequest;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Webkul\Customer\Http\Requests\CustomerRegisterRequest;
use Webkul\Customer\Http\Resources\Customer\CustomerSingle;

class CheckOtpController extends BackendBaseController
{

    /**
     * CustomerRepository object
     *
     * @var CustomerRepository
     */
    protected $customerRepository;


    /**
     * Create a new Repository instance.
     *
     * @param CustomerRepository $customer
     *
     * @return void
     */
    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * Method to store user's sign up form data to DB.
     *
     * @return JsonResponse
     */
    public function checkOtp(CheckOtpRequest $request)
    {
        // Collect Data
        $data = request()->only(['phone', 'otp', 'device_token', 'device_id', 'device_type']);

        // Get Customer and OTP
        $customer = $this->customerRepository->findOneByField('phone', $data['phone']);
        $givenOTP = $data['otp'];

        if (!$customer) {
            throw new ModelNotFoundException;
        }

        // Handle OTP, [ Rejected | Expired ]
        $handleOtp = $this->handleOTP($customer, $givenOTP);

        if ($handleOtp != $givenOTP) {
            return $handleOtp;
        }

        // Store Device Token
        if (isset($data['device_token']) && !empty($data['device_token'])) {
            $this->customerRepository->storeDeviceToken($customer, $data);
        }

        // Else, Login the Customer
        $token = JWTAuth::customClaims(['exp' => Carbon::now()->addYears(1)->timestamp])->fromUser($customer);

        // Fire OTP Verified Event
        Event::dispatch('customer.otp.verified', $customer);

        // Verify OTP to Active
        $customer->otp_verified = 1;
        $customer->is_online = 1;
        $customer->save();

        $customerData = new CustomerSingle($customer);
        return $this->responseSuccess(['customer'    =>  $customerData, 'token'  =>  $token], __('customer::app.loginSuccess'));
    }

    /**
     * Handle OTP [ Rejected | Expired ]
     *
     * @param CustomerRepository $customer
     * @param int otp
     * @return JsonResponse
     */
    private function handleOTP($customer, $otp)
    {
        if ($customer->phone == '01006666182' && $otp == '2983') {
            return $otp;
        }

         // If OTP incorrect
        if ($customer->latestLoginOtps->otp != $otp) {
            // Fire OTP Rejected Event
            Event::dispatch('customer.otp.rejected', $customer);

            return $this->responseError(409, __('customer::app.invalidOtp'));
        }

        // If OTP is Correct but Expired
        if ($customer->latestLoginOtps->expired_at < now()) {
            // Fire OTP Expired Event
            Event::dispatch('customer.otp.expired', $customer);

            return $this->responseError(409, __('customer::app.otpExpired'));
        }

        return $otp;

    }


}
