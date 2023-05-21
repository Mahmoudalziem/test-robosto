<?php


namespace Webkul\Core\Rules;
use Illuminate\Contracts\Validation\Rule;

class ImageBase64 implements Rule
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
        // svg
        // mime_content_type($value)== "image/svg"
        $image= getimagesize($value) ;

        return    $image['mime']=="image/jpeg" ||
            $image['mime']=="image/jpg"  ||
            $image['mime']=="image/png" ;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must has type of :[ png | jpg | jpeg  ] .';
    }
}
