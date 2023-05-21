<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class Creditcard implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        if (!is_numeric($value)) {
            return false;
        }
        
        $checksum = 0;                                  // running checksum total
        $j = 1;                                         // takes value of 1 or 2

        // Process each digit one by one starting at the right
        for ($i = strlen($value) - 1; $i >= 0; $i--) {

            // Extract the next digit and multiply by 1 or 2 on alternative digits.      
            $calc = $value[$i] * $j;

            // If the result is in two digits add 1 to the checksum total
            if ($calc > 9) {
                $checksum = $checksum + 1;
                $calc = $calc - 10;
            }

            // Add the units element to the checksum total
            $checksum = $checksum + $calc;

            // Switch the value of j
            if ($j == 1) {
                $j = 2;
            } else {
                $j = 1;
            };
        }

        // All done - if checksum is divisible by 10, it is a valid modulus 10.
        // If not, report an error.
        if ($checksum % 10 != 0) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('customer::app.ccNumberInvalid');
    }
}
