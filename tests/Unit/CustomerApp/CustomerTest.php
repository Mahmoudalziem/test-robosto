<?php

namespace Tests\Unit\CustomerApp;

//use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\File;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Schema;
use Webkul\Core\Models\Channel;
use Webkul\Customer\Models\Customer;

use Tests\TestCase;
use Webkul\Customer\Repositories\CustomerAddressRepository;
use Webkul\Customer\Repositories\CustomerRepository;


class CustomerTest extends TestCase
{
    public $customer;
    private $customerRepository;
    private $customerAddressRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->customerRepository = resolve(CustomerRepository::class);
        $this->customerAddressRepository = resolve(CustomerAddressRepository::class);
        $this->customer = Customer::find(1);
    }


    public function testCustomerGetAppInfo()
    {
        $response = $this->actingAs($this->customer, 'customer')->json('GET', route('app.customer.app-info'));
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [
                        'customer_support_phone',
                        'callcenter_phone',
                        'email_ask',
                        "social" => [
                            [
                                "url", "icon"
                            ],
                        ]
                    ]
                ]
            );
    }

    public function testCustomerGetAvatars()
    {
        $response = $this->actingAs($this->customer, 'customer')->json('GET', route('avatars.get'));
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [
                        0 => [
                            'id',
                            'image',
                            'image_url',
                            "gender"
                        ]

                    ]
                ]
            );
    }

    public function testCustomerGetAddressIcon()
    {
        $response = $this->actingAs($this->customer, 'customer')->json('GET', route('address-icons.get'));
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [
                        0 => [
                            'id',
                            'image',
                        ]
                    ]
                ]
            );
    }

    public function testCustomerGetBanners()
    {
        $response = $this->actingAs($this->customer, 'customer')->json(
            'GET',
            route('app.customer.banner.list', 'sale'),
            [],
            ['lang' => 'ar']
        );

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [
                        0 => [
                            'id',
                            'area',
                            'name',
                            'section',
                            'status',
                            'default',
                            'image',
                            'created_at',
                        ]
                    ]
                ]
            );
    }

    public function testCustomerProfile()
    {
        $response = $this->actingAs($this->customer, 'customer')->json('GET', route('app.customer.profile'));

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(
                [
                    'status', 'success',
                    'data' => [
                        "id",
                        "channel_id",
                        "name",
                        "email",
                        "gender",
                        "date_of_birth",
                        "default_address",
                        "addresses",
                        "avatar",
                        "phone",
                        "landline",
                        "notes",
                        "wallet",
                        "is_flagged",
                        "otp_verified",
                        "avatar_url",
                        "avatar_id",
                        "status",
                        "created_at",
                        "updated_at"
                    ]
                ]
            );
    }

    public function testCustomerUpdateValidation()
    {
        $customerID = $this->customer->id;
        $data = [];

        $response = $this->actingAs($this->customer, 'customer')->putJson(
            route('app.customer.update', $customerID),
            $data
        );
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name', 'avatar_id', 'avatar_id'
            ], 'data.errors');
    }

    public function testCustomerUpdate()
    {
        $data = [
            'name' => 'Robosto Customer',
            'avatar_id' => 3,
            'email' => "new.customer0012@gmail.com"
        ];

        $response = $this->actingAs($this->customer, 'customer')->putJson(
            route('app.customer.update'),
            $data
        );

        $response->assertStatus(200)
            ->assertJson(['status' => 200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'Robosto Customer',
            'avatar_id' => 3,
            'email' => "new.customer0012@gmail.com"
        ]);
    }

    // public function testCreateCustomerCardValidation()
    // {
    //     $data = [];

    //     $this->actingAs($this->customer, 'customer')->postJson(
    //         route('app.customer.cards.add'),
    //         $data
    //     )
    //         ->assertStatus(422)
    //         ->assertJsonValidationErrors([
    //             'card_number', 'card_exp', 'card_cvc', 'card_name'
    //         ], 'data.errors');
    // }

    // public function testCreateCustomerCard()
    // {
    //     $customerID = $this->customer->id;

    //     $data = [
    //         'card_number'  =>  4573764244938304,
    //         'card_exp'   =>  2105,
    //         'card_cvc'   =>  123,
    //         'card_name'   =>  'Test Card'
    //     ];

    //     $this->actingAs($this->customer, 'customer')->postJson(
    //         route('app.customer.cards.add'),
    //         $data
    //     )
    //         ->assertStatus(200)
    //         ->assertJson(['status'    =>  200])
    //         ->assertJsonStructure([
    //             'status', 'success', 'message', 'data'
    //         ]);

    //     $this->assertDatabaseHas('vapulus_cards', [
    //         'customer_id' => $customerID,
    //         'last_digits'  =>  '2346'
    //     ]);
    // }
}
