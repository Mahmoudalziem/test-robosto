<?php

namespace Tests\Unit\CustomerApp;

use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Repositories\CustomerRepository;

class CustomerSettingsTest extends TestCase
{
    public $customer;
    private $customerRepository;


    public function setUp(): void
    {
        parent::setUp();
        $this->customerRepository = resolve(CustomerRepository::class);
        $this->customer = Customer::find(1);
    }

    public function testCustomerGetAppInfo()
    {
        $response = $this->actingAs($this->customer, 'customer')->json('GET', route('app.customer.settings.get'));
        
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => []
                ]
            );
    }

    public function testCustomerSettingsUpdate()
    {
        $data = [
            'lang' => 'ar',
            'app_notification' => 1,
            'email_notification' => 1,
            'sms_notification' => 0,
        ];

        $response = $this->actingAs($this->customer, 'customer')->putJson(
            route('app.customer.settings.update'),
            $data
        );

        $response->assertStatus(200)
            ->assertJson(['status' => 200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseHas('customer_settings', [
            'key' => 'lang',
            'value' => 'ar',
        ]);
        $this->assertDatabaseHas('customer_settings', [
            'key' => 'app_notification',
            'value' => 1,
        ]);
        $this->assertDatabaseHas('customer_settings', [
            'key' => 'email_notification',
            'value' => 1,
        ]);
        $this->assertDatabaseHas('customer_settings', [
            'key' => 'sms_notification',
            'value' => 0,
        ]);
    }

}
