<?php

namespace Webkul\Product\Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class ProductTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('product_sub_categories')->delete();
        DB::table('products')->delete();
        DB::table('product_translations')->delete();

        $products = array(
            array(
                "id" => 1,
                "barcode" => "8707808790875",
                "image" => "product\/1\/Robosto-2020110514373408018000.jpeg",
                "thumb" => null,
                "featured" => null,
                "status" => 1,
                "returnable" => 1,
                "price" => 7.00,
                "cost" => null,
                "tax" => 0,
                "weight" => 0.50,
                "width" => 10.00,
                "height" => 10.00,
                "length" => 10.00,
                "brand_id" => 1,
                "unit_id" => 3,
                "unit_value" => "500",
                "created_at" => "2020-11-05 14:37:34.0",
                "updated_at" => "2020-11-05 14:37:34.0"
            ),
            array(
                "id" => 2,
                "barcode" => "8707808790800",
                "image" => "product\/2\/Robosto-2020110514391878637300.jpeg",
                "thumb" => null,
                "featured" => null,
                "status" => 1,
                "returnable" => 1,
                "price" => 20.00,
                "cost" => null,
                "tax" => 0,
                "weight" => 1.00,
                "width" => 20.00,
                "height" => 10.00,
                "length" => 20.00,
                "brand_id" => 1,
                "unit_id" => 4,
                "unit_value" => "1",
                "created_at" => "2020-11-05 14:39:18.0",
                "updated_at" => "2020-11-05 14:39:18.0"
            ),
            array(
                "id" => 3,
                "barcode" => "8707808790879",
                "image" => "product\/3\/Robosto-2020110514483001862600.jpeg",
                "thumb" => null,
                "featured" => null,
                "status" => 1,
                "returnable" => 1,
                "price" => 30.00,
                "cost" => null,
                "tax" => 0,
                "weight" => 0.50,
                "width" => 10.00,
                "height" => 10.00,
                "length" => 10.00,
                "brand_id" => 1,
                "unit_id" => 3,
                "unit_value" => "500",
                "created_at" => "2020-11-05 14:44:49.0",
                "updated_at" => "2020-11-05 14:48:30.0"
            ),
            array(
                "id" => 4,
                "barcode" => "8707808790805",
                "image" => "product\/4\/Robosto-2020110514481988580400.jpeg",
                "thumb" => null,
                "featured" => null,
                "status" => 1,
                "returnable" => 1,
                "price" => 25.00,
                "cost" => null,
                "tax" => 0,
                "weight" => 1.00,
                "width" => 20.00,
                "height" => 20.00,
                "length" => 20.00,
                "brand_id" => 1,
                "unit_id" => 4,
                "unit_value" => "1",
                "created_at" => "2020-11-05 14:48:19.0",
                "updated_at" => "2020-11-05 14:48:19.0"
            ),
            array(
                "id" => 5,
                "barcode" => "8707808790802",
                "image" => "product\/5\/Robosto-2020110515104403860900.jpeg",
                "thumb" => null,
                "featured" => null,
                "status" => 1,
                "returnable" => 1,
                "price" => 50.00,
                "cost" => null,
                "tax" => 0,
                "weight" => 0.90,
                "width" => 10.00,
                "height" => 10.00,
                "length" => 20.00,
                "brand_id" => 2,
                "unit_id" => 3,
                "unit_value" => "900",
                "created_at" => "2020-11-05 15:10:44.0",
                "updated_at" => "2020-11-05 15:10:44.0"
            ),
            array(
                "id" => 6,
                "barcode" => "8707808790801",
                "image" => "product\/6\/Robosto-2020110515385314394900.jpeg",
                "thumb" => null,
                "featured" => null,
                "status" => 1,
                "returnable" => 1,
                "price" => 7.00,
                "cost" => null,
                "tax" => 0,
                "weight" => 0.15,
                "width" => 10.00,
                "height" => 10.00,
                "length" => 10.00,
                "brand_id" => 3,
                "unit_id" => 3,
                "unit_value" => "150",
                "created_at" => "2020-11-05 15:38:52.0",
                "updated_at" => "2020-11-05 15:38:53.0"
            ),
            array(
                "id" => 7,
                "barcode" => "23242353434",
                "image" => "product\/7\/Robosto-2020110515412043758900.jpeg",
                "thumb" => null,
                "featured" => null,
                "status" => 1,
                "returnable" => 0,
                "price" => 25.00,
                "cost" => null,
                "tax" => 1,
                "weight" => 0.75,
                "width" => 10.00,
                "height" => 10.00,
                "length" => 20.00,
                "brand_id" => 4,
                "unit_id" => 8,
                "unit_value" => "20",
                "created_at" => "2020-11-05 15:41:20.0",
                "updated_at" => "2020-11-05 15:41:20.0"
            ),
            array(
                "id" => 8,
                "barcode" => "622123610097688",
                "image" => "product\/8\/Robosto-2020110515573268870800.jpeg",
                "thumb" => null,
                "featured" => null,
                "status" => 1,
                "returnable" => 0,
                "price" => 30.00,
                "cost" => null,
                "tax" => 1,
                "weight" => 0.19,
                "width" => 10.00,
                "height" => 10.00,
                "length" => 20.00,
                "brand_id" => 6,
                "unit_id" => 3,
                "unit_value" => "185",
                "created_at" => "2020-11-05 15:57:32.0",
                "updated_at" => "2020-11-10 17:48:35.0"
            ),
            array(
                "id" => 9,
                "barcode" => "8707808790879",
                "image" => "product\/9\/Robosto-2020110515582983923300.jpeg",
                "thumb" => null,
                "featured" => null,
                "status" => 1,
                "returnable" => 0,
                "price" => 20.00,
                "cost" => null,
                "tax" => 0,
                "weight" => 0.50,
                "width" => 101.00,
                "height" => 10.00,
                "length" => 10.00,
                "brand_id" => 5,
                "unit_id" => 8,
                "unit_value" => "1",
                "created_at" => "2020-11-05 15:58:29.0",
                "updated_at" => "2020-11-05 15:58:29.0"
            ),
            array(
                "id" => 10,
                "barcode" => "23242353434",
                "image" => "product\/10\/Robosto-2020110516060180578500.jpeg",
                "thumb" => null,
                "featured" => null,
                "status" => 1,
                "returnable" => 0,
                "price" => 150.00,
                "cost" => null,
                "tax" => 1,
                "weight" => 1.00,
                "width" => 10.00,
                "height" => 10.00,
                "length" => 20.00,
                "brand_id" => 7,
                "unit_id" => 4,
                "unit_value" => "1",
                "created_at" => "2020-11-05 16:06:01.0",
                "updated_at" => "2020-11-05 16:06:01.0"
            ),
            array(
                "id" => 11,
                "barcode" => "6223001241553",
                "image" => "product\/11\/Robosto-2020111210555160656200.jpeg",
                "thumb" => null,
                "featured" => null,
                "status" => 1,
                "returnable" => 1,
                "price" => 9.00,
                "cost" => null,
                "tax" => 0,
                "weight" => 0.30,
                "width" => 20.00,
                "height" => 10.00,
                "length" => 10.00,
                "brand_id" => 8,
                "unit_id" => 8,
                "unit_value" => "550",
                "created_at" => "2020-11-12 10:55:51.0",
                "updated_at" => "2020-11-12 10:55:51.0"
            ),
            array(
                "id" => 12,
                "barcode" => "622300350065",
                "image" => "product\/12\/Robosto-2020111212053394523200.jpeg",
                "thumb" => null,
                "featured" => null,
                "status" => 1,
                "returnable" => 1,
                "price" => 17.00,
                "cost" => null,
                "tax" => 0,
                "weight" => 1.00,
                "width" => 7.00,
                "height" => 25.00,
                "length" => 5.00,
                "brand_id" => 2,
                "unit_id" => 6,
                "unit_value" => "1",
                "created_at" => "2020-11-12 12:05:33.0",
                "updated_at" => "2020-11-12 12:05:33.0"
            ),
            array(
                "id" => 13,
                "barcode" => "87078087908423",
                "image" => "product\/13\/Robosto-2020111212123858430800.jpeg",
                "thumb" => null,
                "featured" => null,
                "status" => 1,
                "returnable" => 1,
                "price" => 50.00,
                "cost" => null,
                "tax" => 0,
                "weight" => 0.50,
                "width" => 20.00,
                "height" => 10.00,
                "length" => 10.00,
                "brand_id" => 1,
                "unit_id" => 3,
                "unit_value" => "500",
                "created_at" => "2020-11-12 12:12:38.0",
                "updated_at" => "2020-11-12 12:12:38.0"
            ),
            array(
                "id" => 15,
                "barcode" => "8707808790123",
                "image" => "product\/15\/Robosto-2020111213231413174200.jpeg",
                "thumb" => null,
                "featured" => null,
                "status" => 1,
                "returnable" => 1,
                "price" => 5.00,
                "cost" => null,
                "tax" => 0,
                "weight" => 0.50,
                "width" => 20.00,
                "height" => 10.00,
                "length" => 10.00,
                "brand_id" => 1,
                "unit_id" => 3,
                "unit_value" => "500",
                "created_at" => "2020-11-12 13:23:14.0",
                "updated_at" => "2020-11-12 13:23:14.0"
            ),
            array(
                "id" => 16,
                "barcode" => "999ona",
                "image" => "product\/16\/Robosto-2020111217500840623600.png",
                "thumb" => null,
                "featured" => null,
                "status" => 1,
                "returnable" => 1,
                "price" => 12.50,
                "cost" => null,
                "tax" => 2,
                "weight" => 0.75,
                "width" => 12.00,
                "height" => 45.00,
                "length" => 2.00,
                "brand_id" => 1,
                "unit_id" => 1,
                "unit_value" => ".750",
                "created_at" => "2020-11-12 17:50:08.0",
                "updated_at" => "2020-11-12 17:50:08.0"
            ),
            array(
                "id" => 23,
                "barcode" => "87678576798098089",
                "image" => "product\/23\/Robosto-2020111611492984149500.jpeg",
                "thumb" => "product\/23\/Robosto-Thumb-2020111611492984149500.jpeg",
                "featured" => null,
                "status" => 1,
                "returnable" => 0,
                "price" => 100.00,
                "cost" => null,
                "tax" => 0,
                "weight" => 0.50,
                "width" => 20.00,
                "height" => 20.00,
                "length" => 10.00,
                "brand_id" => 8,
                "unit_id" => 1,
                "unit_value" => "2",
                "created_at" => "2020-11-16 11:49:29.0",
                "updated_at" => "2020-11-16 11:51:34.0"
            )
        );
        DB::table('products')->insert($products);

        $product_translations = array(
            array(
                "id" => 1,
                "name" => "طماطم",
                "description" => "طماطم",
                "locale" => "ar",
                "product_id" => 1
            ),
            array(
                "id" => 2,
                "name" => "tomatoes",
                "description" => "tomatoes",
                "locale" => "en",
                "product_id" => 1
            ),
            array(
                "id" => 3,
                "name" => "جزر",
                "description" => "جزر",
                "locale" => "ar",
                "product_id" => 2
            ),
            array(
                "id" => 4,
                "name" => "carrot",
                "description" => "carrot",
                "locale" => "en",
                "product_id" => 2
            ),
            array(
                "id" => 5,
                "name" => "فراولة",
                "description" => "فراولة",
                "locale" => "ar",
                "product_id" => 3
            ),
            array(
                "id" => 6,
                "name" => "strawberry",
                "description" => "strawberry",
                "locale" => "en",
                "product_id" => 3
            ),
            array(
                "id" => 7,
                "name" => "مانجو",
                "description" => "مانجو",
                "locale" => "ar",
                "product_id" => 4
            ),
            array(
                "id" => 8,
                "name" => "mango",
                "description" => "mango",
                "locale" => "en",
                "product_id" => 4
            ),
            array(
                "id" => 9,
                "name" => "جبنة كريمى",
                "description" => "جبنة كريمى",
                "locale" => "ar",
                "product_id" => 5
            ),
            array(
                "id" => 10,
                "name" => "cream cheese",
                "description" => "cream cheese",
                "locale" => "en",
                "product_id" => 5
            ),
            array(
                "id" => 11,
                "name" => "جيلى فاكهة",
                "description" => "جيلى فاكهة\n",
                "locale" => "ar",
                "product_id" => 6
            ),
            array(
                "id" => 12,
                "name" => "jelly fruits",
                "description" => "jelly fruits\n",
                "locale" => "en",
                "product_id" => 6
            ),
            array(
                "id" => 13,
                "name" => "انجلش تي- أحمد تي",
                "description" => "انجلش تي- أحمد تي",
                "locale" => "ar",
                "product_id" => 7
            ),
            array(
                "id" => 14,
                "name" => "English tea-Ahmad tea",
                "description" => "English tea-Ahmad tea",
                "locale" => "en",
                "product_id" => 7
            ),
            array(
                "id" => 15,
                "name" => "تونة صن شاين قطع",
                "description" => "تونة صن شاين قطع",
                "locale" => "ar",
                "product_id" => 8
            ),
            array(
                "id" => 16,
                "name" => "Tuna sunshine 1 peace",
                "description" => "Tuna sunshine 1 peace",
                "locale" => "en",
                "product_id" => 8
            ),
            array(
                "id" => 17,
                "name" => "الخبز التركى",
                "description" => "الخبز التركى",
                "locale" => "ar",
                "product_id" => 9
            ),
            array(
                "id" => 18,
                "name" => "Turkish bread",
                "description" => "Turkish bread\n",
                "locale" => "en",
                "product_id" => 9
            ),
            array(
                "id" => 19,
                "name" => "اكل كلاب بيربو ",
                "description" => "اكل كلاب بيربو ",
                "locale" => "ar",
                "product_id" => 10
            ),
            array(
                "id" => 20,
                "name" => "Birbo dog food",
                "description" => "Birbo dog food",
                "locale" => "en",
                "product_id" => 10
            ),
            array(
                "id" => 21,
                "name" => "مناديل كلاسيك ناعمة",
                "description" => "كلاسيك 550 منديل ناعم \nمتوسط ( 275*2طبقة )\n100 % صحية ونقية\nجودة عالية\nاصلي\n100 % لب ورقى نقي\nيستخدم مرة واحدة فقط\nامن على الاطفال\nزينة .... دايما جنبك\nصنع في مصر\n",
                "locale" => "ar",
                "product_id" => 11
            ),
            array(
                "id" => 22,
                "name" => "classic soft tissues",
                "description" => "classic 550 soft tissues\naverage ( 275 * 2 ply )\n100 % pure & hygienic\nsuper quality\noriginal\n100 % virgin pulp\nused only one time\nsafe to kids\nZeina .... always there for you\nmade in Egypt\n",
                "locale" => "en",
                "product_id" => 11
            ),
            array(
                "id" => 23,
                "name" => "لبن ( كامل الدسم )",
                "description" => "100 % لبن بقري 3 % دسم كالسيوم وفيتامينات بدون لبن بودرة كالسيوم بروتين فيتامين د فيتامين ب 12 فيتامين ا  بوتاسيوم",
                "locale" => "ar",
                "product_id" => 12
            ),
            array(
                "id" => 24,
                "name" => "Milk ( full cream )",
                "description" => "100 % cow milk 3% fat with calcium & vitamins no powder milk protein vitamin d vitamin B12 vitamin A potassium\n",
                "locale" => "en",
                "product_id" => 12
            ),
            array(
                "id" => 25,
                "name" => "توت بري",
                "description" => "توت بري",
                "locale" => "ar",
                "product_id" => 13
            ),
            array(
                "id" => 26,
                "name" => "blueberry",
                "description" => "blueberry ",
                "locale" => "en",
                "product_id" => 13
            ),
            array(
                "id" => 29,
                "name" => "خيار",
                "description" => "خيار",
                "locale" => "ar",
                "product_id" => 15
            ),
            array(
                "id" => 30,
                "name" => "cucumber",
                "description" => "cucumber",
                "locale" => "en",
                "product_id" => 15
            ),
            array(
                "id" => 31,
                "name" => "y9a",
                "description" => "لبن جهينة خالي الدسم   لتر2",
                "locale" => "ar",
                "product_id" => 16
            ),
            array(
                "id" => 32,
                "name" => "5uh",
                "description" => "Summer Potatos",
                "locale" => "en",
                "product_id" => 16
            ),
            array(
                "id" => 45,
                "name" => "test4",
                "description" => "test4",
                "locale" => "ar",
                "product_id" => 23
            ),
            array(
                "id" => 46,
                "name" => "test4",
                "description" => "test4",
                "locale" => "en",
                "product_id" => 23
            )
        );
        DB::table('product_translations')->insert($product_translations);

        $product_sub_categories = array(
            array(
                "product_id" => 1,
                "sub_category_id" => 1
            ),
            array(
                "product_id" => 2,
                "sub_category_id" => 1
            ),
            array(
                "product_id" => 3,
                "sub_category_id" => 2
            ),
            array(
                "product_id" => 4,
                "sub_category_id" => 2
            ),
            array(
                "product_id" => 5,
                "sub_category_id" => 3
            ),
            array(
                "product_id" => 6,
                "sub_category_id" => 5
            ),
            array(
                "product_id" => 7,
                "sub_category_id" => 4
            ),
            array(
                "product_id" => 8,
                "sub_category_id" => 7
            ),
            array(
                "product_id" => 9,
                "sub_category_id" => 6
            ),
            array(
                "product_id" => 10,
                "sub_category_id" => 8
            ),
            array(
                "product_id" => 11,
                "sub_category_id" => 13
            ),
            array(
                "product_id" => 12,
                "sub_category_id" => 10
            ),
            array(
                "product_id" => 13,
                "sub_category_id" => 2
            ),
            array(
                "product_id" => 15,
                "sub_category_id" => 1
            ),
            array(
                "product_id" => 16,
                "sub_category_id" => 1
            ),
            array(
                "product_id" => 16,
                "sub_category_id" => 2
            ),
            array(
                "product_id" => 23,
                "sub_category_id" => 17
            )
        );
        DB::table('product_sub_categories')->insert($product_sub_categories);
    }
}