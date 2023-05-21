<?php

namespace Tests\Unit\Portal\Notification;

use Tests\TestCase;
use Webkul\User\Models\Admin;
use App\Jobs\PublishNotifications;
use Webkul\User\Models\Notification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

class NotificationTest extends TestCase
{
    private $admin;

    public function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::find(1);
    }

    public function testCreateNotificationValidation()
    {
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.notifications.store'),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'title', 'body'
            ], 'data.errors');
    }

    public function testCreateNotification()
    {
        $notificationsCountBeforeCreate = Notification::count();

        $data = [
            'title'  =>  'Test Notification title',
            'body' =>  'Test Notification Body',
            'tags'  =>  [1, 2]
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.notifications.store'),
            $data
        );
        $response
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $newNotificationID = $response['data']['id'];

        $this->assertDatabaseCount('notifications', $notificationsCountBeforeCreate + 1);

        $this->assertDatabaseHas('notifications', [
            'id'    =>  $newNotificationID,
            'title'  =>  'Test Notification title',
            'body' => 'Test Notification Body',
        ]);

        $this->assertDatabaseHas('notification_tags', [
            'notification_id'    =>  $newNotificationID,
            'tag_id'  =>  1
        ]);
    }

    public function testCreateScheduledNotification()
    {
        Queue::fake();

        $notificationsCountBeforeCreate = Notification::count();

        $data = [
            'title'  =>  'Test Notification title',
            'body' =>  'Test Notification Body',
            'scheduled_at'  =>  now()->addMinutes(2)->format('Y-m-d h:i:s'),
            'tags'  =>  [1, 2]
        ];

        $response = $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.notifications.store'),
            $data
        );
        $response
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $newNotificationID = $response['data']['id'];

        $this->assertDatabaseCount('notifications', $notificationsCountBeforeCreate + 1);

        $this->assertDatabaseHas('notifications', [
            'id'    =>  $newNotificationID,
            'title'  =>  'Test Notification title',
            'body' => 'Test Notification Body',
        ]);

        $this->assertDatabaseHas('notification_tags', [
            'notification_id'    =>  $newNotificationID,
            'tag_id'  =>  1
        ]);
    }

    public function testUpdateNotificationValidation()
    {
        $notificationID = Notification::latest()->first()->id;
        $data = [];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.notifications.update', $notificationID),
            $data
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'title', 'body','tags'
            ], 'data.errors');
    }

    public function testUpdateNotification()
    {
        $notificationID = Notification::latest()->first()->id;
        $data = [
            'title'  =>  'Title Updated',
            'body' =>  'Body Updated',
            'tags'  =>  [1, 2]
        ];

        $this->actingAs($this->admin, 'admin')->postJson(
            route('admin.app-management.notifications.update', $notificationID),
            $data
        )
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseHas('notifications', [
            'id'  =>  $notificationID,
            'title' => 'Title Updated',
        ]);
        
        $this->assertDatabaseHas('notification_tags', [
            'notification_id'    =>  $notificationID,
            'tag_id'  =>  1
        ]);        
    }

    public function testGettingNotifications()
    {

        $this->actingAs($this->admin, 'admin')->getJson(route('admin.app-management.notifications.index'))
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure(
                [
                    'status', 'success', 'message',
                    'data'  =>  [
                        0   =>  [
                            'id',
                            'title',
                            'body',
                            'scheduled_at',
                            'tags'
                        ]
                    ]
                ]
            );
    }

    public function testShowNotification()
    {
        $notification = Notification::first();

        $this->actingAs($this->admin, 'admin')->getJson(
            route('admin.app-management.notifications.show', $notification)
        )
            ->assertStatus(200)
            ->assertJson(['success' =>  true])
            ->assertJsonStructure([
                'status',
                'success',
                'data'  =>  [
                    'id',
                    'title',
                    'body',
                    'scheduled_at',
                    'tags'
                ]
            ]);
    }


    public function testDeleteNotification()
    {
        $notificationID = Notification::latest()->first()->id;

        $this->actingAs($this->admin, 'admin')->postJson(route('admin.app-management.notifications.delete', $notificationID))
            ->assertStatus(200)
            ->assertJson(['status'    =>  200])
            ->assertJsonStructure([
                'status', 'success', 'data'
            ]);

        $this->assertDatabaseMissing('notifications', [
            'id'  =>  $notificationID,
        ]);
    }
}
