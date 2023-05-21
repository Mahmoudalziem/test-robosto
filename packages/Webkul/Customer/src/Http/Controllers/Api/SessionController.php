<?php

namespace Webkul\Customer\Http\Controllers\Api;

use Illuminate\Support\Facades\Event;
use Tymon\JWTAuth\Facades\JWTAuth;
use Webkul\API\Http\Resources\Customer\Customer as CustomerResource;
use Webkul\Customer\Http\Controllers\Controller;
use Webkul\Customer\Http\Requests\CustomerLoginRequest;
use Webkul\Customer\Repositories\CustomerRepository;


class SessionController extends Controller
{

    /**
     * Contains current guard
     *
     * @var string
     */
    protected $guard;
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    /**
     * Create a new Repository instance.
     *
     * @return void
    */

    protected $customerRepository;
    public function __construct(CustomerRepository $customerRepository)
    {

        $this->guard = request()->bearerToken() ? 'customer-api' : 'customer';
        auth()->setDefaultDriver($this->guard);
        $this->middleware('auth:' . $this->guard, ['only' => ['get', 'update', 'logout']]);
        $this->_config = request('_config');
        $this->customerRepository = $customerRepository;

    }

    public function login( CustomerLoginRequest $request)
    {
        if (! auth()->guard($this->guard)->attempt(request(['email', 'password']))) {
            return responder()->error(  401,"Invalid Email Or Password")->respond();
        }

        if (auth()->guard($this->guard)->user()->status == 0) {
            auth()->guard($this->guard)->logout();
            return responder()->error( 203,"Customer not activated" )->respond();
        }

        if (auth()->guard($this->guard)->user()->is_verified == 0) {
            auth()->guard($this->guard)->logout();
            return responder()->error( 203,"Customer is not verified" )->respond();
        }
        $customer = auth($this->guard)->user();

        $token = JWTAuth::fromUser($customer);

        return responder()->success(['token'=>$token,'customer'=>$customer]);

    }

    /**
     * Get details for current logged in customer
     *
     * @return \Illuminate\Http\Response
     */
    public function profile()
    {
        $customer = auth($this->guard)->user();
        return response()->json([
            'data' => new CustomerResource($customer),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update()
    {
        $customer = auth($this->guard)->user();

        $this->validate(request(), [
            'first_name'    => 'required',
            'last_name'     => 'required',
            'gender'        => 'required',
            'date_of_birth' => 'nullable|date|before:today',
            'email'         => 'email|unique:customers,email,' . $customer->id,
            'password'      => 'confirmed|min:6',
        ]);

        $data = request()->all();

        if (! $data['date_of_birth']) {
            unset($data['date_of_birth']);
        }

        if (!isset($data['password']) || ! $data['password']) {
            unset($data['password']);
        } else {
            $data['password'] = bcrypt($data['password']);
        }

        $this->customerRepository->update($data, $customer->id);

        return response()->json([
            'message' => 'Your account has been created successfully.',
            'data'    => new CustomerResource($this->customerRepository->find($customer->id)),
        ]);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function logout($id)
    {
        auth()->guard('customer')->logout();
        Event::dispatch('customer.after.logout', $id);
        return responder()->success(['message'=>'Customer logged out!']);
    }
}