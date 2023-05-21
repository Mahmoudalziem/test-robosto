<?php

namespace Webkul\Admin\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use Webkul\Area\Models\Area;
use Webkul\User\Models\Role;
use Webkul\User\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\User\Models\TransactionTicket;
use Webkul\Core\Services\SendPushNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Webkul\Admin\Http\Requests\Accounting\AccountantUpdateTransactionRequest;
use Webkul\Driver\Models\DriverTransactionRequest;
use Webkul\Core\Http\Controllers\BackendBaseController;
use Webkul\Admin\Http\Resources\Accounting\DriverTransactions;
use Webkul\Admin\Repositories\Accounting\AccountingRepository;
use Webkul\Admin\Http\Resources\Accounting\AreaManagerTransactions;
use Webkul\Admin\Http\Requests\Accounting\AreaManagerTransactionRequest;
use Webkul\Admin\Http\Requests\Accounting\AreaManagerUpdateTransactionRequest;
use Webkul\User\Models\AreaManagerTransactionRequest as AreaManagerTransactionRequestModel;
use Webkul\Admin\Http\Resources\Accounting\TransactionTicket as AreaManagerTransactionTicket;

class AccountingController extends BackendBaseController
{
    protected $accountingRepository;

    public function __construct(AccountingRepository $accountingRepository)
    {
        $this->accountingRepository = $accountingRepository;
    }

    /**
     * @param Request $request
     * 
     * @return [type]
     */
    public function driverTransactions(Request $request)
    {
        $transactions = $this->accountingRepository->driverTransactions($request);

        $data['transactions'] = new DriverTransactions($transactions);

        $data['current_wallet'] = auth('admin')->user()->areaManagerWallet ? auth('admin')->user()->areaManagerWallet->wallet : 0;
        $data['pending_wallet'] = auth('admin')->user()->areaManagerWallet ? auth('admin')->user()->areaManagerWallet->pending_wallet : 0;

        return $this->customResponsePaginatedSuccess($data, null, $request);
    }
    
    
    /**
     * @param Request $request
     * 
     * @return [type]
     */
    public function generateDriverTransactionOtp(Request $request, $transactionId)
    {
        // Get the Transaction
        $transaction = DriverTransactionRequest::findOrFail($transactionId);

        if ($transaction->status != DriverTransactionRequest::STATUS_PENDING) {
            return $this->responseError(422, 'this transaction is not pending');
        }

        $otp = rand(1000, 9999);
        $transaction->admin_id = auth('admin')->id();
        $transaction->otp = $otp;
        $transaction->save();

        return $this->responseSuccess(['otp' => $otp], null);
    }
    
    
    /**
     * @param Request $request
     * 
     * @return [type]
     */
    public function areaManagerTransactions(Request $request)
    {
        // if (!auth('admin')->user()->hasRole([Role::AREA_MANAGER, Role::SUPER_ADMIN])) {
        //     return $this->responseError(403, "You don't have permission to do this process");
        // }

        $transactions = $this->accountingRepository->areaManagerTransactions($request);

        $data['transactions'] = new AreaManagerTransactions($transactions);

        $data['current_wallet'] = auth('admin')->user()->areaManagerWallet ? auth('admin')->user()->areaManagerWallet->wallet : 0;
        $data['pending_wallet'] = auth('admin')->user()->areaManagerWallet ? auth('admin')->user()->areaManagerWallet->pending_wallet : 0;

        return $this->customResponsePaginatedSuccess($data, null, $request);
    }
    
    
    /**
     * @param Request $request
     * 
     * @return [type]
     */
    public function accountantTransactions(Request $request)
    {
        if (!auth('admin')->user()->hasRole([Role::ACCOUNTANT, Role::SUPER_ADMIN])) {
            return $this->responseError(403, "You don't have permission to do this process");
        }

        $transactions = $this->accountingRepository->accountantTransactions($request);

        $data['transactions'] = new AreaManagerTransactions($transactions);

        $data['current_wallet'] = auth('admin')->user()->accountantWallet ? auth('admin')->user()->accountantWallet->wallet : 0;
        $data['pending_wallet'] = auth('admin')->user()->accountantWallet ? auth('admin')->user()->accountantWallet->pending_wallet : 0;

        return $this->customResponsePaginatedSuccess($data, null, $request);
    }
    
    
    /**
     * @param Request $request
     * 
     * @return [type]
     */
    public function areaManagerTransactionTickets(Request $request, $transactionId)
    {
        $tickets = TransactionTicket::where('transaction_id', $transactionId)->get();

        $data = new AreaManagerTransactionTicket($tickets);

        return $this->responseSuccess($data, 'Tickets');
    }


    /**
     * @param Request $request
     * 
     * @return JsonResponse
     */
    public function areaManagerTransactionRequest(AreaManagerTransactionRequest $request)
    {
        $data = $request->only(['area_id', 'amount', 'transaction_id', 'transaction_date', 'image', 'note']);

        $areaManager = auth('admin')->user();
        $wallet = auth('admin')->user()->areaManagerWallet ? auth('admin')->user()->areaManagerWallet->wallet : 0;
        $area = Area::findOrFail($data['area_id']);

        if (!$areaManager->hasRole([Role::AREA_MANAGER])) {
            return $this->responseError(403, "You don't have permission to do this process");
        }

        // the area manager cannot make request if his wallet less than given amount
        // OR -> has PENDING transaction
        if ($wallet < $data['amount']) {
            return $this->responseError(406, "Your wallet doesn't has this amount");
        }

        // Save Transaction Data
        $transaction = $this->accountingRepository->areaManagerTransactionRequest($areaManager, $data);

        // Save Ticket if provided
        if (isset($data['note']) && !empty($data['note'])) {
            $transaction->tickets()->create([
                'sender_id' =>  $areaManager->id,
                'note'      =>  $data['note'],
            ]);
        }

        // Hold Amount from [ Area and Area-Manager]
        $area->pendingMoney($data['amount'], $areaManager->id);
        $areaManager->areaManagerPendingMoney($data['amount'], $data['area_id']);

        // Send Transaction Notification To Accounntant Manager
        $this->sendTransactionNotificationToAccounntant($areaManager, $data['amount']);

        return $this->responseSuccess();
    }


    /**
     * @param Request $request
     * 
     * @return mixed
     */
    public function areaManagerUpdateTransactionRequest(AreaManagerUpdateTransactionRequest $request, $transactionId)
    {
        $data = $request->only(['note', 'status']);
        $transaction = AreaManagerTransactionRequestModel::findOrFail($transactionId);
        $sender = auth('admin')->user();

        if (!$sender->hasRole([Role::AREA_MANAGER])) {
            return $this->responseError(403, "You don't have permission to do this process");
        }

        if ($transaction->status == AreaManagerTransactionRequestModel::STATUS_RECEIVED || $transaction->status == AreaManagerTransactionRequestModel::STATUS_CANCELLED) {
            return $this->responseError(406, 'The Transaction already Finished');
        }

        // Update Transaction Status
        $transaction->status = $request->status;
        $transaction->save();

        // Save Ticket if provided
        $this->saveNewTicket($transaction, $sender->id, $data);

        // if AreaManager Cancelled The Transaction, Return Amount to Area and AreaManager Wallet
        if ($data['status'] == AreaManagerTransactionRequestModel::STATUS_CANCELLED) {
            // Subtract Pending Money from Area and AreaManager
            $transaction->area->pendingMoneyCancelled($transaction->amount);
            $transaction->areaManager->admin->areaManagerPendingMoneyCancelled($transaction->amount);
        }

        return $this->responseSuccess();
    }
    
    /**
     * @param Request $request
     * 
     * @return mixed
     */
    public function accountantUpdateTransactionRequest(AccountantUpdateTransactionRequest $request, $transactionId)
    {
        $data = $request->only(['note', 'status']);
        $transaction = AreaManagerTransactionRequestModel::findOrFail($transactionId);
        $sender = auth('admin')->user();

        if (!$sender->hasRole([Role::ACCOUNTANT])) {
            return $this->responseError(403, "You don't have permission to do this process");
        }

        if ($transaction->status == AreaManagerTransactionRequestModel::STATUS_RECEIVED || $transaction->status == AreaManagerTransactionRequestModel::STATUS_CANCELLED) {
            return $this->responseError(406, 'The Transaction already Finished');
        }

        // Update Transaction Status
        $transaction->status = $request->status;
        $transaction->accountant_id = auth('admin')->id();
        $transaction->save();
        
        // Save Ticket if provided
        $this->saveNewTicket($transaction, $sender->id, $data);

        // if Accountant Accept Transaction, Subtract Amount from Area and AreaManager
        if ($data['status'] == AreaManagerTransactionRequestModel::STATUS_RECEIVED) {
            // Subtract Pending Money from Area and AreaManager
            $transaction->area->pendingMoneyReceived($transaction->amount, auth('admin')->id());
            $transaction->areaManager->admin->areaManagerPendingMoneyReceived($transaction->amount, auth('admin')->id());

            $transaction->accountant->accountantAddMoney($transaction->amount, $transaction->area_id, $transaction->area_manager_id);
        }

        return $this->responseSuccess();
    }

    /**
     * @param AreaManagerTransactionRequestModel $transaction
     * @param array $data
     * 
     * @return bool
     */
    private function saveNewTicket(AreaManagerTransactionRequestModel $transaction, int $senderId, array $data)
    {
        // Save Ticket if provided
        if (isset($data['note']) && !empty($data['note'])) {
            $transaction->tickets()->create([
                'sender_id' =>  $senderId,
                'note'      =>  $data['note'],
            ]);
        }

        return true;
    }

    /**
     * @param $data
     * @param null $message
     * @param $request
     * @return JsonResponse
     */
    protected function customResponsePaginatedSuccess($data, $message = null, $request)
    {
        $response = null;
        if ($data['transactions']->resource instanceof LengthAwarePaginator) {
            $response = $data['transactions']->toResponse($request)->getData();
        }

        $response->current_wallet = isset($data['current_wallet']) ? $data['current_wallet'] : null;
        $response->pending_wallet = isset($data['pending_wallet']) ? $data['pending_wallet'] : null;
        $response->status = 200;
        $response->success = true;
        $response->message = $message;

        return response()->json($response);
    }


    /**
     * Handle the event.
     * 
     * @param Driver $driver
     * @param float $amount
     * @return bool
     * @throws InvalidOptionsException
     * 
     * @return [type]
     */
    private function sendTransactionNotificationToAccounntant(Admin $areaManager, float $amount = null)
    {
        $accountant = Admin::whereHas('roles', function ($q) {
            $q->where('roles.slug', Role::ACCOUNTANT);
        })->get();

        $tokens = [];
        foreach ($accountant as $admin) {
            $tokens = array_merge($tokens, $admin->deviceToken->pluck('token')->toArray());
        }

        $data = [
            'title' => "New Transaction from Area Manager {$areaManager->name}",
            'body' => "Area Manager {$areaManager->name} wants to give you {$amount} EGP",
            'data'  =>  [
                'key'   =>  'new_area_manager_transaction'
            ]
        ];

        return SendPushNotification::send($tokens, $data);
    }
}
