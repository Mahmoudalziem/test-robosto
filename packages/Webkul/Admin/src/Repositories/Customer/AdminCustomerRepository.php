<?php

namespace Webkul\Admin\Repositories\Customer;

use Illuminate\Support\Facades\Log;
use Webkul\Core\Eloquent\Repository;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Models\WalletNote;
use Webkul\Customer\Contracts\Customer as CustomerContract;
use Webkul\Promotion\Models\PromotionVoidDevice;

class AdminCustomerRepository extends Repository {

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model() {
        return CustomerContract::class;
    }

    public function list($request, bool $export = false) {
        $query = $this->newQuery();

        //$query = app(App\User::class)->newQuery()->with('group');
        // handle sort option
        if ($request->has('sort') && !empty($request->sort)) {

            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'desc');
        }

        // area  // source  // date1,date2
        if ($request->exists('area_id') && !empty($request['area_id'])) {
            $query->whereHas('addresses', function ($q) use ($request) {
                $q->whereHas('area', function ($q) use ($request) {
                    $q->where('id', '=', $request['area_id']);
                });
            });
        }

        // area  // source  // date1,date2
        if ($request->exists('device_type') && !empty($request['device_type'])) {
            $query->whereHas('deviceToken', function ($q) use ($request) {
                $q->where('device_type', '=', $request['device_type']);
            });
        }

        if ($request->exists('channel_id') && !empty($request['channel_id'])) {
            $query->whereHas('channel', function ($q) use ($request) {
                $q->where('id', '=', $request['channel_id']);
            });
        }

        if ($request->exists('tag_id') && !empty($request['tag_id'])) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('tags.id', '=', $request['tag_id']);
            });
        }

        if ($request->exists('date_from') && !empty($request['date_from']) && $request->exists('date_to') && !empty($request['date_to'])) {
            $query->where(function ($q) use ($request) {
                $dateFrom = $request['date_from'] . ' 00:00:00';
                $dateTo = $request['date_to'] . ' 23:59:59';
                $q->whereBetween('created_at', [$dateFrom, $dateTo]);
            });
        }

        if ($export) {
            return $query;
        }


        $perPage = $request->has('per_page') ? (int) $request->per_page : null;

        $pagination = $query->with('addresses')->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }

    public function search($request) {
        $customer = new Customer();
        Log::info("Before");
        Log::info($request['filter']);
        $filter = trim($this->clean($request['filter']));
        Log::info("After");
        Log::info($filter);
        //return  $customer = Customer::search($filter)->paginate();
        $customer = $customer->search($filter);

        // handle sort option
        if ($request->has('sort') && !empty($request->sort)) {
            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $customer = $customer->orderBy($sortCol, $sortDir);
            }
        } else {

            $customer = $customer->orderBy('id', 'desc');
        }

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;
        $pagination = $customer->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }

    public function invitersList($customer, $request) {
        $inviters = $customer->inviters()->pluck('id')->toArray();

        $query = Customer::whereIn('id', $inviters);

        // handle sort option
        if ($request->has('sort') && !empty($request->sort)) {

            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'desc');
        }

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;

        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }

    public function orderslist($customer, $request) {
        // $query = $this->newQuery();

        $query = $customer->orders();
        // handle sort option
        if ($request->has('sort') && !empty($request->sort)) {

            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'desc');
        }

        // Search by Status
        if ($request->exists('status') && !empty($request['status'])) {
            $query->where('status', $request['status']);
        }

        // Search by Area
        if ($request->exists('area_id') && !empty($request['area_id'])) {
            $query->where('area_id', $request['area_id']);
        }


        if ($request->exists('channel_id') && !empty($request['channel_id'])) {
            $query->whereHas('channel', function ($q) use ($request) {
                $q->where('id', '=', $request['channel_id']);
            });
        }

        if ($request->exists('filter') && !empty($request['filter'])) {
            $query->where('increment_id', $request['filter']);
        }


        if ($request->exists('date_from') && !empty($request['date_from']) && $request->exists('date_to') && !empty($request['date_to'])) {
            $query->where(function ($q) use ($request) {
                $dateFrom = $request['date_from'] . ' 00:00:00';
                $dateTo = $request['date_to'] . ' 23:59:59';
                $q->whereBetween('created_at', [$dateFrom, $dateTo]);
            });
        }



        $perPage = $request->has('per_page') && $request->per_page == 9 ? (int) $request->per_page : 9;

        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }
    
    // List Customer Devices
    public function devicesList($request) {
        $customer = $this->model->findOrFail($request['customer_id']);

        if($customer->uniqueDevices()){
            $query=PromotionVoidDevice::whereIn('deviceid',$customer->uniqueDevices()->pluck('deviceid') )  ;
        }

        // handle sort option
        if ($request->has('sort') && !empty($request->sort)) {
            // handle multisort
            $sorts = explode(',', $request->sort);
            foreach ($sorts as $sort) {
                list($sortCol, $sortDir) = explode('|', $sort);
                $query = $query->orderBy($sortCol, $sortDir);
            }
        } else {
            $query = $query->orderBy('id', 'desc');
        }

        $perPage = $request->has('per_page') ? (int) $request->per_page : null;

        $pagination = $query->paginate($perPage);
        $pagination->appends([
            'sort' => $request->sort,
            'filter' => $request->filter,
            'per_page' => $request->per_page
        ]);

        return $pagination;
    }        

    /**
     * @param Customer $customer
     * @param mixed $data
     *
     * @return void
     */
    public function updateCustomerWallet(Customer $customer, $data) {

        $data['wallet_before'] = $customer->wallet;

        // Create Customer Wallet Note
        $walletNote = $this->saveWalletNote($customer, $data);

        // Create Customer Wallet Items
        $this->saveWalletCustomerItems($walletNote, $data);

        // Update Customer Wallet based on given flag
        if ($data['flag'] == WalletNote::ADD_MONEY) {
            $this->addMoneyToCustomerWallet($customer, $data);
        } elseif ($data['flag'] == WalletNote::SUBTRACT_MONEY) {
            $this->subtractMoneyFromCustomerWallet($customer, $data);
        }

        return true;
    }

    /**
     * @param Customer $customer
     * @param mixed $data
     *
     * @return void
     */
    private function addMoneyToCustomerWallet(Customer $customer, $data) {
        // Update Customer Wallet
        $customer->addMoneyToWallet($data['admin_id'], $data['amount'], $data['text']);
    }

    /**
     * @param Customer $customer
     * @param mixed $data
     *
     * @return void
     */
    private function subtractMoneyFromCustomerWallet(Customer $customer, $data) {
        // Update Customer Wallet
        $customer->subtractMoneyFromWallet($data['admin_id'], $data['amount'], $data['text']);
    }

    /**
     * @param Customer $customer
     * @param mixed $data
     *
     * @return void
     */
    private function saveWalletNote(Customer $customer, $data) {
        return $customer->walletNotes()->create([
                    'text' => $data['text'],
                    'amount' => $data['amount'],
                    'wallet_before' => $data['wallet_before'],
                    'type' => $data['flag'],
                    'admin_id' => $data['admin_id'],
                    'order_id' => $data['order_id']??null,
                    'reason_id' => $data['reason_id']??null,
        ]);
    }

    /**
     * @param Customer $customer
     * @param mixed $data
     *
     * @return void
     */
    private function saveWalletCustomerItems(WalletNote $walletNote, $data) {
        // check if there is array of products in the wallet update request
        if (isset($data['products']) && count($data['products']) > 0) {
            foreach ($data['products'] as $item) {
                $insert = [
                    'order_id' => isset($data['order_id']) && !empty($data['order_id']) ? $data['order_id'] : null,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                ];
                $walletNote->items()->create($insert);
            }
        }
    }

    function clean($string) {
        // + - = && || > < ! ( ) { } [ ] ^ " ~ * ? : \ /
        $search = array('+', '-', '=', '&&', '||', '>', '<', '!', '(', ')', '{', '}', '[', ']', '^', '"' . '~', '*', '?', ':', "/");
        return str_replace($search, '', $string);
    }

}
