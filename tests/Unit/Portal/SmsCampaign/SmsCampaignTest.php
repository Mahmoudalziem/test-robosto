<?php

namespace Tests\Unit\Portal\SmsCampaign;

use Tests\TestCase;
use Webkul\User\Models\Admin;
use App\Jobs\PublishNotifications;

use Webkul\Core\Models\SmsCampaign;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

class SmsCampaignTest extends TestCase {

    private $admin;

    public function setUp(): void {
        parent::setUp();
        $this->admin = Admin::find(1);
    }

    public function testCreateSmsCampaignValidation() {
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
                        route('admin.marketing.sms-campaign.store'),
                        $data
                )
                ->assertStatus(422)
                ->assertJsonValidationErrors([
                    'content', 'tags'
                        ], 'data.errors');
    }

    public function testCreateSmsCampaign() {
        $smsCampaignsCountBeforeCreate = SmsCampaign::count();

        $data = [
           'content' => 'Test Sms Campaign title',
           'tags' => [1, 2]
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson(
                route('admin.marketing.sms-campaign.store'),
                $data
        );
        $response
                ->assertStatus(200)
                ->assertJson(['status' => 200])
                ->assertJsonStructure([
                    'status', 'success', 'data'
        ]);

        $newSmsCampaignID = $response['data']['id'];

        $this->assertDatabaseCount('sms_campaigns', $smsCampaignsCountBeforeCreate + 1);

        $this->assertDatabaseHas('sms_campaigns', [
            'id' => $newSmsCampaignID,
           'content' => 'Test Sms Campaign title',
            
        ]);

        $this->assertDatabaseHas('sms_campaign_tags', [
            'sms_campaign_id' => $newSmsCampaignID,
            'tag_id' => 1
        ]);
    }

    public function testCreateScheduledSmsCampaign() {
        Queue::fake();

        $smsCampaignsCountBeforeCreate = SmsCampaign::count();

        $data = [
            'content' => 'Test Sms Campaign title',
            'scheduled_at' => now()->addMinutes(2)->format('Y-m-d h:i:s'),
            'tags' => [1, 2],
            'filter' => [
                "gender" => "1",
                "area_id" => "1",
                "channel_id" => "2",
                "date_from" => "2020-01-01",
                "date_to" => "2021-04-01",
                "device_type" => "ios"
                ]
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson(
                route('admin.markting.push-campaign.sms-campaign.store'),
                $data
        );
        $response
                ->assertStatus(200)
                ->assertJson(['status' => 200])
                ->assertJsonStructure([
                    'status', 'success', 'data'
        ]);

        $newSmsCampaignID = $response['data']['id'];

        $this->assertDatabaseCount('sms_campaigns', $smsCampaignsCountBeforeCreate + 1);

        $this->assertDatabaseHas('sms_campaigns', [
            'id' => $newSmsCampaignID,
            'content' => 'Test Sms Campaign title',
        ]);

        $this->assertDatabaseHas('sms_campaign_tags', [
            'sms_campaign_id' => $newSmsCampaignID,
            'tag_id' => 1
        ]);
    }

    public function testGettingSmsCampaings() {

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.marketing.sms-campaign.index'))
                ->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure(
                        [
                            'status', 'success', 'message',
                            'data' => [
                                0 => [
                                    'id',
                                    'content',
                                    'tags',
                                    'filter' ,                                   
                                    'scheduled_at',
                                    'is_pushed',

                                ]
                            ]
                        ]
        );
    }

}
