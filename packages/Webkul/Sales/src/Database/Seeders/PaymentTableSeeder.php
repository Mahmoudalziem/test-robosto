<?php

namespace Webkul\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class PaymentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('payment_methods')->delete();
        DB::table('payment_method_translations')->delete();

        $paymentMethods = array(
            array(
                "id" => 1,
                "slug" => "COD",
                "status" => 1,
                "created_at" => now(),
                "updated_at" => now()
            ),
            array(
                "id" => 2,
                "slug" => "CC",
                "status" => 1,
                "created_at" => now(),
                "updated_at" => now()
            )
        );
        DB::table('payment_methods')->insert($paymentMethods);

        $paymentMethodsTranslations = array(
            array(
                "id" => 1,
                "title" => "COD",
                "description" => null,
                "locale" => "ar",
                "payment_method_id" => 1
            ),
            array(
                "id" => 2,
                "title" => "COD",
                "description" => null,
                "locale" => "en",
                "payment_method_id" => 1
            ),
            array(
                "id" => 3,
                "title" => "CC",
                "description" => null,
                "locale" => "ar",
                "payment_method_id" => 2
            ),
            array(
                "id" => 4,
                "title" => "CC",
                "description" => null,
                "locale" => "en",
                "payment_method_id" => 2
            ),
        );
        DB::table('payment_method_translations')->insert($paymentMethodsTranslations);
    }
}
