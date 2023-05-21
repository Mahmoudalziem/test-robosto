<?php

namespace Webkul\Core\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TagsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tags')->delete();

        $now = Carbon::now();

        DB::table('tags')->insert([

            'name'        => 'new-user',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        DB::table('tags')->insert([
            'name'        => 'first-order',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        DB::table('tags')->insert([
            'name'        => 'second-order',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        DB::table('tags')->insert([
            'name'        => 'x-order',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

    }
}
