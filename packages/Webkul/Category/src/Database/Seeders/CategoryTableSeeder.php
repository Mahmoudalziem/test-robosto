<?php

namespace Webkul\Category\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Category\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Webkul\Category\Models\SubCategory;

class CategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('category_sub_categories')->delete();
        DB::table('sub_categories')->delete();
        DB::table('sub_category_translations')->delete();
        DB::table('categories')->delete();
        DB::table('category_translations')->delete();


        $categories = array(
            array(
                "id" => 1,
                "position" => 1,
                "image" => "category\/1\/Robosto-2020111614184213158400.png",
                "thumb" => "category\/1\/Robosto-Thumb-2020111614184213158400.png",
                "status" => 1,
                "created_at" => "2020-11-05 13:41:11.0",
                "updated_at" => "2020-11-16 14:18:42.0"
            ),
            array(
                "id" => 2,
                "position" => 1,
                "image" => "category\/2\/Robosto-2020111614193068961900.png",
                "thumb" => "category\/2\/Robosto-Thumb-2020111614193068961900.png",
                "status" => 1,
                "created_at" => "2020-11-05 15:06:12.0",
                "updated_at" => "2020-11-16 14:19:30.0"
            ),
            array(
                "id" => 3,
                "position" => 1,
                "image" => "category\/3\/Robosto-2020111614201569724400.png",
                "thumb" => "category\/3\/Robosto-Thumb-2020111614201569724400.png",
                "status" => 1,
                "created_at" => "2020-11-05 15:22:09.0",
                "updated_at" => "2020-11-16 14:20:15.0"
            ),
            array(
                "id" => 4,
                "position" => 1,
                "image" => "category\/4\/Robosto-2020111614211129638500.png",
                "thumb" => "category\/4\/Robosto-Thumb-2020111614211129638500.png",
                "status" => 1,
                "created_at" => "2020-11-05 15:29:05.0",
                "updated_at" => "2020-11-16 14:21:11.0"
            ),
            array(
                "id" => 5,
                "position" => 1,
                "image" => "category\/5\/Robosto-2020111614215536005700.png",
                "thumb" => "category\/5\/Robosto-Thumb-2020111614215536005700.png",
                "status" => 1,
                "created_at" => "2020-11-05 15:29:26.0",
                "updated_at" => "2020-11-16 14:21:55.0"
            ),
            array(
                "id" => 6,
                "position" => 1,
                "image" => "category\/6\/Robosto-2020111614223676681200.png",
                "thumb" => "category\/6\/Robosto-Thumb-2020111614223676681200.png",
                "status" => 1,
                "created_at" => "2020-11-05 15:47:25.0",
                "updated_at" => "2020-11-16 14:22:36.0"
            ),
            array(
                "id" => 7,
                "position" => 1,
                "image" => "category\/7\/Robosto-2020110516014997938900.jpeg",
                "thumb" => null,
                "status" => 0,
                "created_at" => "2020-11-05 16:01:49.0",
                "updated_at" => "2020-11-16 14:24:15.0"
            ),
            array(
                "id" => 8,
                "position" => 1,
                "image" => "category\/8\/Robosto-2020111614244056724000.png",
                "thumb" => "category\/8\/Robosto-Thumb-2020111614244056724000.png",
                "status" => 1,
                "created_at" => "2020-11-11 11:09:48.0",
                "updated_at" => "2020-11-16 14:24:40.0"
            ),
            array(
                "id" => 9,
                "position" => 1,
                "image" => "category\/9\/Robosto-2020111111325914179900.jpeg",
                "thumb" => null,
                "status" => 0,
                "created_at" => "2020-11-11 11:32:59.0",
                "updated_at" => "2020-11-16 14:23:13.0"
            ),
            array(
                "id" => 13,
                "position" => 1,
                "image" => "category\/13\/Robosto-2020111614171559965700.png",
                "thumb" => "category\/13\/Robosto-Thumb-2020111614171559965700.png",
                "status" => 1,
                "created_at" => "2020-11-16 14:17:15.0",
                "updated_at" => "2020-11-16 14:17:15.0"
            )
        );
        DB::table('categories')->insert($categories);

        $category_translations = array(
            array(
                "id" => 1,
                "name" => "خضروات و فاكهة",
                "locale" => "ar",
                "category_id" => 1
            ),
            array(
                "id" => 2,
                "name" => "Vegetables& Fruits ",
                "locale" => "en",
                "category_id" => 1
            ),
            array(
                "id" => 3,
                "name" => "منتجات الالبان",
                "locale" => "ar",
                "category_id" => 2
            ),
            array(
                "id" => 4,
                "name" => "Dairy Products",
                "locale" => "en",
                "category_id" => 2
            ),
            array(
                "id" => 5,
                "name" => "سناكس و حلوى",
                "locale" => "ar",
                "category_id" => 3
            ),
            array(
                "id" => 6,
                "name" => "Snacks& Candy",
                "locale" => "en",
                "category_id" => 3
            ),
            array(
                "id" => 7,
                "name" => "مخبوزات",
                "locale" => "ar",
                "category_id" => 4
            ),
            array(
                "id" => 8,
                "name" => "Bakery",
                "locale" => "en",
                "category_id" => 4
            ),
            array(
                "id" => 9,
                "name" => "مشروبات",
                "locale" => "ar",
                "category_id" => 5
            ),
            array(
                "id" => 10,
                "name" => "Beverages",
                "locale" => "en",
                "category_id" => 5
            ),
            array(
                "id" => 11,
                "name" => "معلبات",
                "locale" => "ar",
                "category_id" => 6
            ),
            array(
                "id" => 12,
                "name" => "Canned Products",
                "locale" => "en",
                "category_id" => 6
            ),
            array(
                "id" => 13,
                "name" => "منتجات الحيوانات",
                "locale" => "ar",
                "category_id" => 7
            ),
            array(
                "id" => 14,
                "name" => "Pet care ",
                "locale" => "en",
                "category_id" => 7
            ),
            array(
                "id" => 15,
                "name" => "منتجات النظافة",
                "locale" => "ar",
                "category_id" => 8
            ),
            array(
                "id" => 16,
                "name" => "Cleaning Supplies",
                "locale" => "en",
                "category_id" => 8
            ),
            array(
                "id" => 17,
                "name" => "منتجات ورقية",
                "locale" => "ar",
                "category_id" => 9
            ),
            array(
                "id" => 18,
                "name" => "paper producs",
                "locale" => "en",
                "category_id" => 9
            ),
            array(
                "id" => 25,
                "name" => "منتجات الطفل",
                "locale" => "ar",
                "category_id" => 13
            ),
            array(
                "id" => 26,
                "name" => " Baby Care",
                "locale" => "en",
                "category_id" => 13
            )
        );
        DB::table('category_translations')->insert($category_translations);


        $sub_categories = array(
            array(
                "id" => 1,
                "position" => 1,
                "image" => "subcategory\/1\/Robosto-2020110513383872366300.jpeg",
                "thumb" => null,
                "status" => 1,
                "created_at" => "2020-11-05 13:38:38.0",
                "updated_at" => "2020-11-12 15:50:39.0"
            ),
            array(
                "id" => 2,
                "position" => 1,
                "image" => "subcategory\/2\/Robosto-2020110514425171903100.jpeg",
                "thumb" => null,
                "status" => 1,
                "created_at" => "2020-11-05 14:41:31.0",
                "updated_at" => "2020-11-05 14:42:51.0"
            ),
            array(
                "id" => 3,
                "position" => 1,
                "image" => "subcategory\/3\/Robosto-2020110515082156598200.jpeg",
                "thumb" => null,
                "status" => 1,
                "created_at" => "2020-11-05 15:04:13.0",
                "updated_at" => "2020-11-05 15:08:21.0"
            ),
            array(
                "id" => 4,
                "position" => 1,
                "image" => "subcategory\/4\/Robosto-2020110515143282261200.png",
                "thumb" => null,
                "status" => 1,
                "created_at" => "2020-11-05 15:14:32.0",
                "updated_at" => "2020-11-05 15:14:32.0"
            ),
            array(
                "id" => 5,
                "position" => 1,
                "image" => "subcategory\/5\/Robosto-2020110515175570206600.jpeg",
                "thumb" => null,
                "status" => 1,
                "created_at" => "2020-11-05 15:17:55.0",
                "updated_at" => "2020-11-05 15:17:55.0"
            ),
            array(
                "id" => 6,
                "position" => 1,
                "image" => "subcategory\/6\/Robosto-2020110515281593699500.jpeg",
                "thumb" => null,
                "status" => 1,
                "created_at" => "2020-11-05 15:28:15.0",
                "updated_at" => "2020-11-05 15:28:15.0"
            ),
            array(
                "id" => 7,
                "position" => 1,
                "image" => "subcategory\/7\/Robosto-2020110515470577169600.jpeg",
                "thumb" => null,
                "status" => 1,
                "created_at" => "2020-11-05 15:47:05.0",
                "updated_at" => "2020-11-05 15:47:05.0"
            ),
            array(
                "id" => 8,
                "position" => 1,
                "image" => "subcategory\/8\/Robosto-2020110516014096548800.jpeg",
                "thumb" => null,
                "status" => 1,
                "created_at" => "2020-11-05 16:01:40.0",
                "updated_at" => "2020-11-05 16:01:40.0"
            ),
            array(
                "id" => 9,
                "position" => 1,
                "image" => "subcategory\/9\/Robosto-2020110917235564640600.jpeg",
                "thumb" => null,
                "status" => 1,
                "created_at" => "2020-11-09 17:23:55.0",
                "updated_at" => "2020-11-09 17:23:55.0"
            ),
            array(
                "id" => 10,
                "position" => 1,
                "image" => "subcategory\/10\/Robosto-2020110917292410600100.jpeg",
                "thumb" => null,
                "status" => 1,
                "created_at" => "2020-11-09 17:29:24.0",
                "updated_at" => "2020-11-09 17:29:24.0"
            ),
            array(
                "id" => 11,
                "position" => 1,
                "image" => "subcategory\/11\/Robosto-2020110917354655340400.jpeg",
                "thumb" => null,
                "status" => 1,
                "created_at" => "2020-11-09 17:35:46.0",
                "updated_at" => "2020-11-09 17:35:46.0"
            ),
            array(
                "id" => 12,
                "position" => 1,
                "image" => "subcategory\/12\/Robosto-2020110917401200713200.jpeg",
                "thumb" => null,
                "status" => 1,
                "created_at" => "2020-11-09 17:40:11.0",
                "updated_at" => "2020-11-09 17:40:12.0"
            ),
            array(
                "id" => 13,
                "position" => 1,
                "image" => "subcategory\/13\/Robosto-2020111111324829742500.jpeg",
                "thumb" => null,
                "status" => 1,
                "created_at" => "2020-11-11 11:32:48.0",
                "updated_at" => "2020-11-11 11:32:48.0"
            ),
            array(
                "id" => 14,
                "position" => 1,
                "image" => "subcategory\/14\/Robosto-2020111210334819279800.jpeg",
                "thumb" => null,
                "status" => 1,
                "created_at" => "2020-11-12 10:33:48.0",
                "updated_at" => "2020-11-12 10:33:48.0"
            ),
            array(
                "id" => 15,
                "position" => 1,
                "image" => "subcategory\/15\/Robosto-2020111214121504172000.png",
                "thumb" => null,
                "status" => 0,
                "created_at" => "2020-11-12 14:12:15.0",
                "updated_at" => "2020-11-12 14:57:43.0"
            ),
            array(
                "id" => 16,
                "position" => 1,
                "image" => "subcategory\/16\/Robosto-2020111215150666409900.jpeg",
                "thumb" => null,
                "status" => 1,
                "created_at" => "2020-11-12 15:15:06.0",
                "updated_at" => "2020-11-12 15:15:06.0"
            ),
            array(
                "id" => 17,
                "position" => 1,
                "image" => "subcategory\/17\/Robosto-2020111215584081912100.jpeg",
                "thumb" => null,
                "status" => 1,
                "created_at" => "2020-11-12 15:58:40.0",
                "updated_at" => "2020-11-12 15:58:40.0"
            )
        );
        DB::table('sub_categories')->insert($sub_categories);

        $sub_category_translations = array(
            array(
                "id" => 1,
                "name" => "خضروات",
                "locale" => "ar",
                "sub_category_id" => 1
            ),
            array(
                "id" => 2,
                "name" => "vegetables",
                "locale" => "en",
                "sub_category_id" => 1
            ),
            array(
                "id" => 3,
                "name" => "فاكهة",
                "locale" => "ar",
                "sub_category_id" => 2
            ),
            array(
                "id" => 4,
                "name" => "fruits",
                "locale" => "en",
                "sub_category_id" => 2
            ),
            array(
                "id" => 5,
                "name" => "جبنة ",
                "locale" => "ar",
                "sub_category_id" => 3
            ),
            array(
                "id" => 6,
                "name" => " cheese",
                "locale" => "en",
                "sub_category_id" => 3
            ),
            array(
                "id" => 7,
                "name" => "شاي",
                "locale" => "ar",
                "sub_category_id" => 4
            ),
            array(
                "id" => 8,
                "name" => "Tea",
                "locale" => "en",
                "sub_category_id" => 4
            ),
            array(
                "id" => 9,
                "name" => "حلوى",
                "locale" => "ar",
                "sub_category_id" => 5
            ),
            array(
                "id" => 10,
                "name" => "sweets",
                "locale" => "en",
                "sub_category_id" => 5
            ),
            array(
                "id" => 11,
                "name" => "خبز",
                "locale" => "ar",
                "sub_category_id" => 6
            ),
            array(
                "id" => 12,
                "name" => "bread",
                "locale" => "en",
                "sub_category_id" => 6
            ),
            array(
                "id" => 13,
                "name" => "تونه",
                "locale" => "ar",
                "sub_category_id" => 7
            ),
            array(
                "id" => 14,
                "name" => "Tuna",
                "locale" => "en",
                "sub_category_id" => 7
            ),
            array(
                "id" => 15,
                "name" => "كلاب",
                "locale" => "ar",
                "sub_category_id" => 8
            ),
            array(
                "id" => 16,
                "name" => "Dogs",
                "locale" => "en",
                "sub_category_id" => 8
            ),
            array(
                "id" => 17,
                "name" => "بيض",
                "locale" => "ar",
                "sub_category_id" => 9
            ),
            array(
                "id" => 18,
                "name" => "eggs",
                "locale" => "en",
                "sub_category_id" => 9
            ),
            array(
                "id" => 19,
                "name" => "لبن",
                "locale" => "ar",
                "sub_category_id" => 10
            ),
            array(
                "id" => 20,
                "name" => "milk",
                "locale" => "en",
                "sub_category_id" => 10
            ),
            array(
                "id" => 21,
                "name" => "زبادي",
                "locale" => "ar",
                "sub_category_id" => 11
            ),
            array(
                "id" => 22,
                "name" => "yoghurt",
                "locale" => "en",
                "sub_category_id" => 11
            ),
            array(
                "id" => 23,
                "name" => "زبدة",
                "locale" => "ar",
                "sub_category_id" => 12
            ),
            array(
                "id" => 24,
                "name" => "Butter",
                "locale" => "en",
                "sub_category_id" => 12
            ),
            array(
                "id" => 25,
                "name" => "مناديل ورقية",
                "locale" => "ar",
                "sub_category_id" => 13
            ),
            array(
                "id" => 26,
                "name" => "Tissue",
                "locale" => "en",
                "sub_category_id" => 13
            ),
            array(
                "id" => 27,
                "name" => "شنط ورقية",
                "locale" => "ar",
                "sub_category_id" => 14
            ),
            array(
                "id" => 28,
                "name" => "paper bags",
                "locale" => "en",
                "sub_category_id" => 14
            ),
            array(
                "id" => 29,
                "name" => "test sub",
                "locale" => "ar",
                "sub_category_id" => 15
            ),
            array(
                "id" => 30,
                "name" => "Test sub",
                "locale" => "en",
                "sub_category_id" => 15
            ),
            array(
                "id" => 31,
                "name" => "اكواب ورقية",
                "locale" => "ar",
                "sub_category_id" => 16
            ),
            array(
                "id" => 32,
                "name" => "paper cups",
                "locale" => "en",
                "sub_category_id" => 16
            ),
            array(
                "id" => 33,
                "name" => "صناديق ورقية",
                "locale" => "ar",
                "sub_category_id" => 17
            ),
            array(
                "id" => 34,
                "name" => "paper boxes",
                "locale" => "en",
                "sub_category_id" => 17
            )
        );
        DB::table('sub_category_translations')->insert($sub_category_translations);

        $category_sub_categories = array(
            array(
                "id" => 1,
                "category_id" => 1,
                "sub_category_id" => 1,
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 2,
                "category_id" => 1,
                "sub_category_id" => 2,
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 3,
                "category_id" => 2,
                "sub_category_id" => 3,
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 4,
                "category_id" => 3,
                "sub_category_id" => 5,
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 5,
                "category_id" => 4,
                "sub_category_id" => 6,
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 6,
                "category_id" => 5,
                "sub_category_id" => 4,
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 7,
                "category_id" => 6,
                "sub_category_id" => 7,
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 8,
                "category_id" => 7,
                "sub_category_id" => 8,
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 9,
                "category_id" => 2,
                "sub_category_id" => 9,
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 10,
                "category_id" => 2,
                "sub_category_id" => 10,
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 11,
                "category_id" => 2,
                "sub_category_id" => 11,
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 12,
                "category_id" => 2,
                "sub_category_id" => 12,
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 13,
                "category_id" => 9,
                "sub_category_id" => 13,
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 14,
                "category_id" => 9,
                "sub_category_id" => 14,
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 15,
                "category_id" => 6,
                "sub_category_id" => 15,
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 16,
                "category_id" => 9,
                "sub_category_id" => 16,
                "created_at" => null,
                "updated_at" => null
            ),
            array(
                "id" => 17,
                "category_id" => 9,
                "sub_category_id" => 17,
                "created_at" => null,
                "updated_at" => null
            )
        );
        DB::table('category_sub_categories')->insert($category_sub_categories);
    }
}
