<?php

namespace Tests\Unit\CustomerApp;

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Models\CustomerLoginOtp;

class CustomerAuthTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCustomerRegisterValidation()
    {
        $data = [];
        $this->postJson(route('app.customer.register'), $data)
            ->assertStatus(422)->assertJsonValidationErrors(['phone', 'name', 'avatar_id'], 'data.errors');
    }

    public function testCustomerIsExist()
    {
        $data = [
            'phone'   =>  '01151716662',
            'name'    =>  'Test Customer',
            'avatar_id' =>  1
        ];

        $response = $this->postJson(route('app.customer.register'), $data, ['lang'  =>  'en']);
        
        $response
            ->assertStatus(202)
            ->assertJson(['status'    =>  202])
            ->assertJson(['success'    =>  false])
            ->assertJsonStructure(['status', 'success', 'message']);
    }

    public function testCustomerRegistered()
    {
        $customersCountBeforeCreate = Customer::count();
        $customersLoginOtps = CustomerLoginOtp::count();
        $data = [
            'phone'   =>  '01111111112',
            'name'    =>  'Test Customer',
            'avatar_id' =>  1
        ];

        $response = $this->postJson(route('app.customer.register'), $data, ['lang'  =>  'en']);
        $response->assertStatus(200)->assertJson(['status'    =>  200])
            ->assertJsonStructure(['status', 'success', 'message']);

        $this->assertDatabaseCount('customers', $customersCountBeforeCreate + 1);
        $this->assertDatabaseCount('customer_login_otps', $customersLoginOtps + 1);
        $this->assertDatabaseHas('customers', [
            'phone'   =>  '01111111112',
            'name'    =>  'Test Customer',
            'avatar_id' =>  1
        ]);
    }
 

    public function testCustomerLoginValidation()
    {
        $data = [];
        $this->postJson(route('app.customer.login'), $data)
            ->assertStatus(422)->assertJsonValidationErrors(['phone'], 'data.errors');
    }

    public function testCustomerLoginIsNotExist()
    {
        $data = [
            'phone'   =>  '01146588996',
            'name'    =>  'Test Customer',
            'avatar_id' =>  1
        ];

        $response = $this->postJson(route('app.customer.login'), $data, ['lang'  =>  'en']);
        $response
            ->assertStatus(404)
            ->assertJson(['status'    =>  404])
            ->assertJson(['success'    =>  false])
            ->assertJsonStructure(['status', 'success', 'message']);
    }

    public function testCustomerLoginSuccess()
    {
        $customersLoginOtps = CustomerLoginOtp::count();
        $data = ['phone'   =>  '01111111112'];

        $response = $this->postJson(route('app.customer.login'), $data, ['lang'  =>  'en']);

        $response->assertStatus(200)->assertJson(['status'    =>  200])
            ->assertJsonStructure(['status', 'success', 'message']);

        $this->assertDatabaseCount('customer_login_otps', $customersLoginOtps + 1);
    }

    public function testCustomerCheckOtpValidation()
    {
        $data = [];
        $this->postJson(route('app.customer.checkOtp'), $data)
            ->assertStatus(422)->assertJsonValidationErrors(['phone', 'otp'], 'data.errors');
    }

    public function testCustomerCheckOtpIsNotExist()
    {
        $data = [
            'phone'   =>  '01146588996',
            'otp' =>  '1234'
        ];

        $response = $this->postJson(route('app.customer.checkOtp'), $data, ['lang'  =>  'en']);
        $response
            ->assertStatus(404)
            ->assertJson(['status'    =>  404])
            ->assertJson(['success'    =>  false])
            ->assertJsonStructure(['status', 'success', 'message', 'data']);
    }

    public function testCustomerOtpExpired()
    {
        $customer = Customer::latest()->first();
        $customerLoginOtps = CustomerLoginOtp::where('customer_id', $customer->id)->latest()->first();
        $customerLoginOtps->update(['expired_at'  =>  Carbon::parse($customerLoginOtps->expired_at)->subMinutes(20)]);

        $data = [
            'phone'   =>  $customer->phone,
            'otp' =>  $customerLoginOtps->otp
        ];

        $response = $this->postJson(route('app.customer.checkOtp'), $data, ['lang'  =>  'en']);
        $response
            ->assertStatus(401)
            ->assertJson(['status'    =>  401])
            ->assertJson(['success'    =>  false])
            ->assertJsonStructure(['status', 'success', 'message']);

        // Return Otp to the original value
        $customerLoginOtps->update(['expired_at'  =>  Carbon::parse($customerLoginOtps->expired_at)->addMinutes(20)]);
    }

    public function testCustomerOtpIsInvalid()
    {
        $customer = Customer::latest()->first();
        $customerLoginOtps = CustomerLoginOtp::where('customer_id', $customer->id)->latest()->first();

        $data = [
            'phone'   =>  $customer->phone,
            'otp' =>  (string) ($customerLoginOtps->otp - 1)
        ];

        $response = $this->postJson(route('app.customer.checkOtp'), $data);
        $response->assertStatus(401)->assertJson(['status'    =>  401])
            ->assertJson(['success'    =>  false])
            ->assertJsonStructure(['status', 'success', 'message']);
    }

    public function testCustomerCheckOtpAndLogin()
    {
        $customer = Customer::latest()->first();
        $customerLoginOtps = CustomerLoginOtp::where('customer_id', $customer->id)->latest()->first();

        $data = [
            'phone'   =>  $customer->phone,
            'otp' =>  $customerLoginOtps->otp,
            'device_token'  =>  '$2y$04$xh6EKB4KdAexvC40cv7qI.HWn5ev42rBmZidAo9mhIPyLAZfGWnyu'
        ];

        $response = $this->postJson(route('app.customer.checkOtp'), $data, ['lang'  =>  'en']);

        $response->assertStatus(200)->assertJson(['status'    =>  200])
            ->assertJsonStructure(['status', 'success', 'message']);

        $this->assertDatabaseHas('customer_device_tokens', [
            'customer_id'   =>  $customer->id,
            'token'    =>  '$2y$04$xh6EKB4KdAexvC40cv7qI.HWn5ev42rBmZidAo9mhIPyLAZfGWnyu'
        ]);
    }
}
