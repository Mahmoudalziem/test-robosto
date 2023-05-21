<?php

namespace Webkul\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Category\Database\Seeders\DatabaseSeeder as CategorySeeder;

use Webkul\Core\Database\Seeders\DatabaseSeeder as CoreSeeder;
use Webkul\Area\Database\Seeders\DatabaseSeeder as AreaSeeder;
use Webkul\Brand\Database\Seeders\DatabaseSeeder as BrandSeeder;
use Webkul\User\Database\Seeders\DatabaseSeeder as UserSeeder;
use Webkul\Customer\Database\Seeders\DatabaseSeeder as CustomerSeeder;
use Webkul\Product\Database\Seeders\DatabaseSeeder as ProductSeeder;
use Webkul\Driver\Database\Seeders\DatabaseSeeder as DriverSeeder;
use Webkul\Inventory\Database\Seeders\DatabaseSeeder as InventorySeeder;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        //$this->call(CategorySeeder::class);
        //$this->call(InventorySeeder::class);
       // $this->call(CoreSeeder::class);
       // $this->call(AttributeSeeder::class);
        //$this->call(AreaSeeder::class);
        //$this->call(BrandSeeder::class);
     //   $this->call(UserSeeder::class);
       // $this->call(DriverSeeder::class);
        //$this->call(CustomerSeeder::class);
        //$this->call(ProductSeeder::class);
      //  $this->call(CMSSeeder::class);
       // $this->call(SocialLoginSeeder::class);
    }
}
