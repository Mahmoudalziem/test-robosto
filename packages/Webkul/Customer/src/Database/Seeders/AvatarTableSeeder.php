<?php

namespace Webkul\Customer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AvatarTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('avatars')->delete();
        $avatars = array(
            array(
                "id" => 1,
                "image" => "avatars/F9zGTd8moFv4nGwb71YWneAYDvgs3G4C3tgEXeA0.png",
                "gender" => "1"
            ),
            array(
                "id" => 2,
                "image" => "avatars/yDzDSwXSaOQLg44QFWaNLkwSJ44yJ9uXqvDGqsva.png",
                "gender" => "1"
            ),
            array(
                "id" => 3,
                "image" => "avatars/uauiqRAO1kWfKiFn7utckmcY6buHyGs2mJ9kAnGf.png",
                "gender" => "1"
            ),
            array(
                "id" => 4,
                "image" => "avatars/1aG2pO741uv5TyaK19nzUs0m2qWKe5IKnFzwIuJp.png",
                "gender" => "1"
            ),
            array(
                "id" => 5,
                "image" => "avatars/fPhQYEh8uq4zMZxOpEBrp2h35vCxH7xBiaVNH692.png",
                "gender" => "1"
            ),
            array(
                "id" => 6,
                "image" => "avatars/L4axSu2xdyeQKBDzuYZ8pLyIetjvEMNbeCqBJLhT.png",
                "gender" => "0"
            ),
            array(
                "id" => 7,
                "image" => "avatars/gooSLnxdkZNUPmF7qH60no5zUuhUsTXCi6SxQ179.png",
                "gender" => "0"
            ),
            array(
                "id" => 8,
                "image" => "avatars/yt6cIRsqQ95lfrFnod0s06PU6iIVO1MXJtSQCbxx.png",
                "gender" => "0"
            ),
            array(
                "id" => 9,
                "image" => "avatars/vDd2rPb4YZFAVUfkYa6bMEGYn40OPCHvTfgibscI.png",
                "gender" => "0"
            )
        );
        DB::table('avatars')->insert($avatars);
    }
}
