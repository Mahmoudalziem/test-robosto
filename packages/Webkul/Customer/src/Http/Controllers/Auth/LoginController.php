<?php

namespace Webkul\Customer\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Customer\Http\Requests\CustomerLoginRequest;
use Webkul\Customer\Services\Calls\CallCustomer;

class LoginController extends BackendBaseController
{
    use SMSTrait;

    /**
     * CustomerRepository object
     *
     * @var \Webkul\Customer\Repositories\CustomerRepository
     */
    protected $customerRepository;


    /**
     * Create a new Repository instance.
     *
     * @param  \Webkul\Customer\Repositories\CustomerRepository  $customer
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
     * @return \Illuminate\Http\Response
     */
    public function login(CustomerLoginRequest $request)
    {
        // Collect Data
        $data = $request->only(['phone']);
        // Get Customer and OTP
        $customer = $this->customerRepository->findOneByField('phone', $data['phone']);

        // If Customer Not Found
        if (!$customer) {
            // Fire Customer Doesn't Exist Event
            Event::dispatch('customer.not.found', $customer);

            return $this->responseError(404, __('customer::app.phoneNumberNotExist'));
        }
        // if deactivated return un authorized
        if ($customer->status == 0) {
            // Fire Customer Doesn't Exist Event
            Event::dispatch('customer.deactivated', $customer);

            return $this->responseError(403, __('customer::app.customerDeactivated'));
        }
        $sendCall = false;
        if ($customer->latestLoginOtps && $customer->latestLoginOtps->expired_at > now()) {
           $sendCall = true;
        }

        // Else, Create and Store OTP
        $newOTP = $this->createAndStoreOtp($customer);
        if($sendCall){
            (new CallCustomer)->callCustomerWithOtp($data['phone'], $newOTP);
        }

        // Send OTP via SMS
        $text = __('customer::app.otpMessage', ['otp'   =>  $newOTP]);
        $this->sendSMS($data['phone'], $text);

        // Fire OTP Sent Event
        Event::dispatch('customer.otp.sent', $customer);

        $avatar = $customer->myAvatar ? $customer->avatar : $customer->handleDefaultAvatar()->image;

       return $this->responseSuccess(['avatar'  =>  $avatar], __('customer::app.otpSent'));
    }

    /**
     * Create and Store New OTP
     *
     * @return int otp
     */
    public function createAndStoreOtp($customer)
    {
        $customerOtp = random_int(1000, 9999);
        $customer->loginOtps()->create([
            'otp'           =>  $customerOtp,
            'expired_at'    =>  now()->addMinutes(5)
        ]);

        return $customerOtp;
    }

    public function logout(Request $request) {
        $customer= auth('customer')->user() ;
        Event::dispatch('customer.logout', $customer);
        $customer->is_online = 0;
        $customer->save();
        if($request->device_token){
            $customer->deviceToken()->where('token',$request->device_token)->delete();
        }

        auth('customer')->logout();

        return $this->responseSuccess(null, 'Customer successfully signed out');
    }
}
