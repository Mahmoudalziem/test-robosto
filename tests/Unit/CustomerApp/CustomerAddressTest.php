<?php

namespace Tests\Unit\CustomerApp;

use Tests\TestCase;
use Illuminate\Support\Str;
use Webkul\Customer\Models\Customer;
use Illuminate\Support\Facades\Event;

class CustomerAddressTest extends TestCase
{
    private $customer;
    public function setUp(): void
    {
        parent::setUp();
        $this->customer = Customer::first();
    }

    public function testGettingCustomerAddresses()
    {
        $this->actingAs($this->customer, 'customer')->getJson(route('app.customer.address.list'))
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


    public function testShowCustomerAddress()
    {
        $address = $this->customer->addresses->first();

        $this->actingAs($this->customer, 'customer')->getJson(route('app.customer.address.show', $address))
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

        $this->actingAs($this->customer, 'customer')->postJson(
            route('app.customer.address.add'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'icon_id', 'name', 'address', 'building_no', 'apartment_no', 'floor_no', 'location.lat', 'location.lng'
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

        $this->actingAs($this->customer, 'customer')->postJson(
            route('app.customer.address.add'),
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

        $this->actingAs($this->customer, 'customer')->putJson(
            route('app.customer.address.update', $customerID),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'area_id', 'icon_id', 'name', 'address', 'building_no', 'apartment_no', 'floor_no', 'location.lat', 'location.lng'
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

        $this->actingAs($this->customer, 'customer')->putJson(
            route('app.customer.address.update', $addressID),
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

        $this->actingAs($this->customer, 'customer')->deleteJson(route('app.customer.address.delete', $addressID))
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'message', 'data'
            ]);

        $this->assertSoftDeleted('customer_addresses', [
            'id'    =>  $addressID,
        ]);
    }
}
