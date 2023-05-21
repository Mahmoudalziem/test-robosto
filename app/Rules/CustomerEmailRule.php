<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rule as ValidationRule;
use Webkul\Customer\Models\Customer;

class CustomerEmailRule implements Rule
{
    /**
     * @var String
     */
    private $phone;

    /**
     * Create a new rule instance.
     * @param String $phone
     *
     * @return void
     */
    public function __construct(String $phone = null)
    {
        $this->phone = $phone;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Get Customer By Email
        $customer = Customer::where('email', $value)->first();
        if (!$customer) {
            return true;
        }

        // if that given phone related to the email
        if ($customer->phone == $this->phone) {
            return true;
        }
        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.unique');
    }
}
