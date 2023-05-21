<?php

namespace Webkul\Admin\Http\Requests\Customer;

use App\Http\Requests\ApiBaseRequest;
use Webkul\Customer\Models\WalletCustomerReason;

class CustomerUpdateWalletRequest extends ApiBaseRequest {

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {

        $rules = [
            'text' => 'required|min:5|max:2000',
            'reason_id' => 'required|exists:wallet_customer_reasons,id',
            'amount' => 'required|numeric|min:1',
            'flag' => 'required|in:plus,minus',
        ];
        //$reason = WalletCustomerReason::where(['id' => $this->reason_id, 'type' => WalletCustomerReason::TYPE_PRODUCT])->first();
        $reason = WalletCustomerReason::where(['id' => $this->reason_id])->first();
        if ($reason) {
            if ($reason->type == WalletCustomerReason::TYPE_PRODUCT) {
                $rules['products'] = 'required';
                $rules['products.*.product_id'] = 'distinct';
                if ($this->products) {
                    foreach ($this->products as $key => $product) {
                        $rules['products.' . $key . '.product_id'] = 'required';
                        $rules['products.' . $key . '.qty'] = 'required|integer|min:1|max:' . (int) $product['qty'];
                        $rules['products.' . $key . '.price'] = 'required';
                    }
                }
            }

            if ($reason->type != WalletCustomerReason::TYPE_NONE) {
                $rules['order_id'] = 'required';
            }
        }


        return $rules;
    }

    public function messages() {
        return [
            "products.*.product_id.distinct" => 'This product is already taken!'
        ];
    }

}
