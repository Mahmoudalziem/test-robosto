<?php

namespace Webkul\Admin\Http\Controllers\Notification;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use function DeepCopy\deep_copy;
use Webkul\Core\Models\Notifier;
use Illuminate\Http\JsonResponse;
use App\Jobs\PublishNotifications;
use App\Jobs\NotificationsNotifier;
use Illuminate\Support\Facades\Log;
use Webkul\Customer\Models\Customer;
use Webkul\User\Models\Notification;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Services\SendPushNotification;
use Webkul\Customer\Models\CustomerDeviceToken;
use Webkul\Core\Services\SendNotificationUsingFCM;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Http\Resources\Notification\NotificationAll;
use Webkul\Admin\Http\Requests\Notification\NotificationRequest;
use Webkul\Admin\Repositories\Notification\NotificationRepository;
use Webkul\Admin\Http\Resources\Notification\NotificationSingle as NotificationResource;

class NotificationController extends BackendBaseController {

    /**
     * NotificationRepository object
     *
     * @var \Webkul\Admin\Repositories\Notification\NotificationRepository
     */
    protected $notificationRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Admin\Repositories\Notification\NotificationRepository  $notificationRepository
     * @return void
     */
    public function __construct(NotificationRepository $notificationRepository) {
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request) {
        $notifications = $this->notificationRepository->list($request);

        $data = new NotificationAll($notifications);

        return $this->responsePaginatedSuccess($data, null, $request);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(NotificationRequest $request) {
        $data = $request->only('title', 'body', 'scheduled_at', 'tags', 'filter');

        $notification = $this->notificationRepository->create($data);

        Event::dispatch('notification.created', $notification);

        if (!isset($data['scheduled_at']) || empty($data['scheduled_at'])) {

            // Send Now
            PublishNotifications::dispatch($notification);
        }

        Event::dispatch('admin.log.activity', ['create', 'notification', $notification, auth('admin')->user(), $notification]);

        return $this->responseSuccess($notification);
    }

    /**
     * Show the specified notification.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(int $id) {
        $notification = $this->notificationRepository->findOrFail($id);

        Event::dispatch('notification.show', $notification);

        return $this->responseSuccess(new NotificationResource($notification));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(NotificationRequest $request, $id) {
        $data = $request->only('title', 'body', 'scheduled_at', 'tags');

        $original = $this->notificationRepository->findOrFail($id);
        $before = deep_copy($original);

        $notification = $this->notificationRepository->update($data, $original);

        if (!isset($data['scheduled_at']) || empty($data['scheduled_at'])) {

            // Send Now
            PublishNotifications::dispatch($notification);
        }

        Event::dispatch('notification.updated', $notification);
        Event::dispatch('admin.log.activity', ['update', 'category', $notification, auth('admin')->user(), $notification, $before]);

        return $this->responseSuccess($notification);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function delete($id) {
        $notification = $this->notificationRepository->findOrFail($id);

        $this->notificationRepository->delete($id);

        Event::dispatch('admin.log.activity', ['delete', 'notification', $notification, auth('admin')->user(), $notification]);

        return $this->responseSuccess(null);
    }

    /**
     * @param Notification $notification
     * 
     * @return void
     */
    public function publishNotifications(Notification $notification) {
        $tokens = [];
        $customers = [];
        $customersIDs = [];
        if (count($notification->tags)) {
            // Get Notification with Tags and Customers
            $notificationWithTags = $notification->tags()->with(['customers', 'customers.deviceToken'])->get();
            foreach ($notificationWithTags as $tag) {
                $filters = $notification->filter;
                $query = $tag->customers();
                // get customers by $filters;
                if ($filters && !empty($filters)) {

                    // get customers by phones;
                    if (array_key_exists('phone', $filters) && $filters['phone'] != 'all') { //  $filters['phone'] is array
                        $query->whereIn('phone', $filters['phone']);
                    }

                    // filter by gender
                    if (array_key_exists('gender', $filters) && $filters['gender'] != 'all') { // 1 female ,0 male
                        $query->where('gender', (int) $filters['gender']);
                    }

                    // filter by area
                    if (array_key_exists('area_id', $filters) && $filters['area_id'] != 'all') {
                        $query->whereHas('addresses', function ($q) use ($filters) {
                            $q->whereHas('area', function ($q) use ($filters) {
                                $q->where('id', '=', $filters['area_id']);
                            });
                        });
                    }

                    // filter by channel
                    if (array_key_exists('channel_id', $filters) && $filters['channel_id'] != 'all') {
                        $query->where('channel_id', '=', (int) $filters['channel_id']);
                    }

                    // filter by device type
                    if (array_key_exists('device_type', $filters) && $filters['device_type'] != 'all') {
                        $query->whereHas('deviceToken', function ($q) use ($filters) {
                            $q->where('device_type', '=', $filters['device_type']);
                        });
                    }

                    if (array_key_exists('date_from', $filters) && array_key_exists('date_to', $filters)) {
                        $dateFrom = $filters['date_from'] . ' 00:00:00';
                        $dateTo = $filters['date_to'] . ' 23:59:59';
                        $query->where('customers.created_at', '>=', $dateFrom)
                                ->where('customers.created_at', '<=', $dateTo);
                    }
                }

                $customers = array_merge($customers, $query->get()->pluck('id')->toArray());
            }
            $customersIDs = array_unique($customers);
        } else {
            $customersIDs = Customer::pluck('id')->toArray();
        }

        // Store Customers who will be notify
        if (count($customersIDs)) {
            // Save Customers who will be send notification
            saveNotifiers($customersIDs, Notifier::NOTIFICATION_TYPE, $notification->id);

            // Fire the job that will be send notification
            NotificationsNotifier::dispatch($notification);
        }

        return true;
    }

    /**
     * @param Notification $Notification
     *
     * @return bool
     */
    public function sendToNotifiers(Notification $notification)
    {
        $notifiers = $this->getNotifiers($notification);

        // if no notifiers, then delete old and was sent
        if ($notifiers->isEmpty()) {
            $this->purgeOldNotifiers($notification);
            return true;
        }

        $customersWasSent = [];
        $customers = Customer::whereIn('id', $notifiers->pluck('customer_id')->toArray())->get();
        $data = ['title' => $notification->title, 'body' => $notification->body,];

        foreach ($customers as $customer) {
            $tokens = $customer->deviceToken->pluck('token')->toArray();
            (new SendNotificationUsingFCM())->sendNotification(array_unique($tokens), $data);

            $customersWasSent[] = $customer->id;
        }

        // Update Notifiers to SENT
        $this->updateNotifiers($notification, $customersWasSent);

        NotificationsNotifier::dispatch($notification);
        return true;
    }

    /**
     * @param Notification $Notification
     *
     * @return Collection
     */
    public function getNotifiers(Notification $notification)
    {
        return Notifier::where('entity_type', Notifier::NOTIFICATION_TYPE)
            ->where('entity_id', $notification->id)
            ->where('sent', 0)
            ->limit(20)
            ->get();
    }

    /**
     * @param Notification $Notification
     *
     * @return void
     */
    public function purgeOldNotifiers(Notification $notification)
    {
        Notifier::where('entity_type', Notifier::NOTIFICATION_TYPE)
            ->where('entity_id', $notification->id)
            ->delete();
    }

    /**
     * @param Notification $notification
     * @param array $customer
     * 
     * @return void
     */
    public function updateNotifiers(Notification $notification, $customers)
    {
        Notifier::where('entity_type', Notifier::NOTIFICATION_TYPE)
            ->where('entity_id', $notification->id)
            ->whereIn('customer_id', $customers)
            ->update(['sent'   => 1]);
    }

}
