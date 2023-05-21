<?php

namespace Tests\Unit\Portal\Customer;

use Tests\TestCase;
use Webkul\User\Models\Admin;
use Webkul\Customer\Models\Customer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

class CustomerTest extends TestCase
{
    private $admin;
    private $customer;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::find(1);
        $this->customer = Customer::find(1);
    }

    public function testGettingCustomers()
    {
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.customers.customer.index'))
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success', 'message',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'email',
                            'name',
                            'phone',
                            'landline',
                            'gender',
                            'status',
                            'is_online',
                            'otp_verified',
                            'is_flagged',
                            'source',
                            'wallet',
                            'avatar',
                            'orders_count',
                            'invitationsLogs_count',
                            'created_at'
                        ]
                    ]
                ]
            );
    }


    public function testCustomerSetStatus()
    {
        $data = ['status'   =>  1];
        $customerID = $this->customer->id;

        $this->actingAs($this->admin, 'admin')->putJson(
            route('admin.customers.customer.update-status', $customerID),
            $data
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure([
                'status', 'success', 'message',
                'data'  =>  [
                    'id',
                    'email',
                    'name',
                    'phone',
                    'landline',
                    'gender',
                    'status',
                    'is_online',
                    'otp_verified',
                    'is_flagged',
                    'source',
                    'area',
                    'wallet',
                    'newCustomer',
                    'orders_count',
                    'invitationsLogs_count',
                    'avatar',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    public function testShowCustomer()
    {
        $customerID = $this->customer->id;

        $this->actingAs($this->admin, 'admin')->getJson(
            route('admin.customers.customer.show', $customerID)
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure([
                'status',
                'success',
                'data'  =>  [
                    'id',
                    'email',
                    'name',
                    'phone',
                    'landline',
                    'gender',
                    'status',
                    'is_online',
                    'otp_verified',
                    'is_flagged',
                    'source',
                    'area',
                    'wallet',
                    'newCustomer',
                    'orders_count',
                    'invitationsLogs_count',
                    'avatar',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }


    public function testCreateCustomerValidation()
    {
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.customers.customer.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name', 'phone',
                'addressInfo.area_id', 'addressInfo.name', 'addressInfo.address', 'addressInfo.building_no', 'addressInfo.floor_no', 'addressInfo.apartment_no',
                'addressInfo.location.lat', 'addressInfo.location.lng'
            ], 'data.errors');
    }

    public function testCreateCustomer()
    {
        Event::fake();
        Queue::fake();

        $data = [
            'name'  =>  'Robost Customer',
            'phone' =>  '01142685442',
            'addressInfo' =>  [
                'area_id'   =>  1,
                'name'   =>  'Work',
                'address'   =>  'Address',
                'building_no'   =>  '20',
                'floor_no'   =>  '4',
                'apartment_no'   =>  '16',
                'location'  =>  [
                    'lat'   =>  '29.973814448162734',
                    'lng'   =>  '31.281848427307335',
                ]
            ]
        ];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.customers.customer.store'),
            $data
        )
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'message', 'data'
            ]);

        $this->assertDatabaseHas('customers', [
            'phone' => '01142685442',
            'name'  =>  'Robost Customer'
        ]);
    }

    public function testUpdateCustomerValidation()
    {
        $customerID = $this->customer->id;
        $data = [];

        $this->actingAs($this->admin, 'admin')->putJson(
            route('admin.customers.customer.update', $customerID),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name', 'phone',
            ], 'data.errors');
    }

    public function testUpdateCustomer()
    {
        $customerID = $this->customer->id;
        $data = [
            'name'  =>  'Customer Updated',
            'phone' =>  '01142685444',
        ];

        $this->actingAs($this->admin, 'admin')->putJson(
            route('admin.customers.customer.update', $customerID),
            $data
        )
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'message', 'data'
            ]);

        $this->assertDatabaseHas('customers', [
            'phone' => '01142685444',
            'name'  =>  'Customer Updated'
        ]);
    }

    public function testCallCenterCustomerExist()
    {
        Queue::fake();
        Event::fake();

        $data = [
            'phone' =>  '01142685442',
        ];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.customers.customer.call-center-check-phone'),
            $data
        )
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure(['status', 'success', 'message', 'data']);
    }

    public function testCallCenterCustomerNotExist()
    {
        Queue::fake();
        Event::fake();

        $data = [
            'phone' =>  '01142885442',
        ];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.customers.customer.call-center-check-phone'),
            $data
        )
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure(['status', 'success', 'message', 'data']);
    }

    public function testCallcenterUpdateCustomerValidation()
    {
        $customerID = $this->customer->id;
        $data = [];

        $this->actingAs($this->admin, 'admin')->putJson(
            route('admin.customers.customer.call-center-update-profile', $customerID),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name', 'phone',
                'addressInfo.area_id', 'addressInfo.name', 'addressInfo.address', 'addressInfo.building_no', 'addressInfo.floor_no', 'addressInfo.apartment_no',
                'addressInfo.location.lat', 'addressInfo.location.lng'
            ], 'data.errors');
    }

    public function testCallcenterUpdateCustomer()
    {
        $customerID = $this->customer->id;
        $data = [
            'name'  =>  'Callcenter Updated',
            'phone' =>  '01142685444',
            'addressInfo' =>  [
                'area_id'   =>  1,
                'name'   =>  'Work',
                'address'   =>  'Address',
                'building_no'   =>  '20',
                'floor_no'   =>  '4',
                'apartment_no'   =>  '16',
                'location'  =>  [
                    'lat'   =>  '29.973814448162734',
                    'lng'   =>  '31.281848427307335',
                ]
            ]
        ];

        $this->actingAs($this->admin, 'admin')->putJson(
            route('admin.customers.customer.call-center-update-profile', $customerID),
            $data
        )
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'message', 'data'
            ]);

        $this->assertDatabaseHas('customers', [
            'phone' => '01142685444',
            'name'  =>  'Callcenter Updated'
        ]);
    }

    public function testGettingCustomerAddressList()
    {
        $customerID = $this->customer->id;

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.customers.address.index', $customerID))
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data'  =>  [
                        0   =>  [
                            "id",
                            "customer_id",
                            "area_id",
                            "icon_id",
                            "name",
                            "address",
                            "floor_no",
                            "apartment_no",
                            "building_no",
                            "landmark",
                            "latitude",
                            "longitude",
                            "phone",
                            "is_default",
                            "created_at",
                            "updated_at",
                            "area_name",
                            "icon",
                        ]
                    ]
                ]
            );
    }

    public function testGettingCustomerAddressShow()
    {
        $customerID = $this->customer->id;

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.customers.address.show', $customerID))
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data'  =>  [
                        "id",
                        "customer_id",
                        "area_id",
                        "icon_id",
                        "name",
                        "address",
                        "floor_no",
                        "apartment_no",
                        "building_no",
                        "landmark",
                        "latitude",
                        "longitude",
                        "phone",
                        "is_default",
                        "created_at",
                        "updated_at",
                        "area_name",
                        "icon",
                    ]
                ]
            );
    }

    public function testCreateCustomerAddressValidation()
    {
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.customers.address.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'customer_id', 'area_id', 'icon_id', 'name', 'address', 'building_no', 'apartment_no', 'floor_no', 'location.lat', 'location.lng'
            ], 'data.errors');
    }

    public function testCreateCustomerAddress()
    {
        $customerID = $this->customer->id;

        $data = [
            'customer_id'  =>  $customerID,
            'area_id'   =>  1,
            'icon_id'   =>  1,
            'name'   =>  'Test Address',
            'address'   =>  'Address',
            'building_no'   =>  '20',
            'floor_no'   =>  '4',
            'apartment_no'   =>  '16',
            'location'  =>  [
                'lat'   =>  '29.973814448162734',
                'lng'   =>  '31.281848427307335',
            ]
        ];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.customers.address.store'),
            $data
        )
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'message', 'data'
            ]);

        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $customerID,
            'name'  =>  'Test Address'
        ]);
    }

    public function testUpdateCustomerAddressValidation()
    {
        $customerID = $this->customer->id;
        $data = [];

        $this->actingAs($this->admin, 'admin')->putJson(
            route('admin.customers.address.update', $customerID),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'customer_id', 'icon_id', 'name', 'address', 'building_no', 'apartment_no', 'floor_no', 'location.lat', 'location.lng'
            ], 'data.errors');
    }

    public function testUpdateCustomerAddress()
    {
        $customerID = $this->customer->id;
        $addressID = $this->customer->addresses()->latest()->first()->id;

        $data = [
            'customer_id'  =>  $customerID,
            'area_id'   =>  1,
            'icon_id'   =>  1,
            'name'   =>  'Test Address Updated',
            'building_no'   =>  '25',
            'address'   =>  'Address',
            'floor_no'   =>  '4',
            'apartment_no'   =>  '16',
            'location'  =>  [
                'lat'   =>  '29.973814448162734',
                'lng'   =>  '31.281848427307335',
            ]
        ];

        $this->actingAs($this->admin, 'admin')->putJson(
            route('admin.customers.address.update', $addressID),
            $data
        )
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'message', 'data'
            ]);

        $this->assertDatabaseHas('customer_addresses', [
            'id'    =>  $addressID,
            'name'  =>  'Test Address Updated',
            'building_no'   =>  25
        ]);
    }


    public function testDeleteCustomerAddress()
    {
        $addressID = $this->customer->addresses()->latest()->first()->id;

        $this->actingAs($this->admin, 'admin')->deleteJson(route('admin.customers.address.delete', $addressID))
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'message', 'data'
            ]);

        $this->assertDatabaseMissing('customer_addresses', [
            'id'    =>  $addressID,
        ]);
    }
    
    public function testCustomerOrders()
    {
        $customerID = $this->customer->id;
        $this->actingAs($this->admin, 'admin')->getJson(route('admin.customers.customer.orders',$customerID))
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success', 'message',
                    'data'  =>  [
                        0   =>  [
                            "id",
                            "increment_id",
                            "items_count",
                            "no_of_qty",
                            "status",
                            "status_name",
                            "order_flagged",
                            "flagged_at",
                            "price",
                            "payment_method",
                            "payment_method_title",
                            "area",
                            "warehouse",
                            "driver",
                            "address",
                            "customer_name",
                            "contact_customer",
                            "order_date",
                            "expected_on",
                            "delivered_at",
                        ]
                    ]
                ]
            );
    }

    public function testGettingCustomerInvitationsLogs()
    {
        $customerID = $this->customer->id;

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.customers.customer.invitations-logs', $customerID))
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success', 'data'
                ]
            );
    }
}
