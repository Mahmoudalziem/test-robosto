<?php

namespace Webkul\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Area\Database\Seeders\AreaTableSeeder;
use Webkul\Brand\Database\Seeders\BrandTableSeeder;
use Webkul\Category\Database\Seeders\CategoryTableSeeder;
use Webkul\Collector\Database\Seeders\CollectorTableSeeder;
use Webkul\Customer\Database\Seeders\AddressIconTableSeeder;
use Webkul\Customer\Database\Seeders\AvatarTableSeeder;
use Webkul\Customer\Database\Seeders\CustomerAddressTableSeeder;
use Webkul\Customer\Database\Seeders\CustomerSettingsTableSeeder;
use Webkul\Customer\Database\Seeders\CustomerTableSeeder;
use Webkul\Driver\Database\Seeders\DriverTableSeeder;
use Webkul\Driver\Database\Seeders\MotorTableSeeder;
use Webkul\Inventory\Database\Seeders\InventoryTableSeeder;
use Webkul\Inventory\Database\Seeders\WarehouseTableSeeder;
use Webkul\Product\Database\Seeders\ProductTableSeeder;
use Webkul\Product\Database\Seeders\UnitTableSeeder;
use Webkul\User\Database\Seeders\DatabaseSeeder as UserSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        $this->call(CountriesTableSeeder::class);
        // $this->call(StatesTableSeeder::class);
        $this->call(SettingsTableSeeder::class);
       // $this->call(CustomerSettingsTableSeeder::class);

        $this->call(LocalesTableSeeder::class);
        $this->call(ChannelTableSeeder::class);
        $this->call(AreaTableSeeder::class);
        $this->call(WarehouseTableSeeder::class);
        $this->call(AddressIconTableSeeder::class);
        $this->call(AvatarTableSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(BrandTableSeeder::class);
        $this->call(CategoryTableSeeder::class); // category and subcategory
        $this->call(UnitTableSeeder::class);
        $this->call(ProductTableSeeder::class); //
        $this->call(CustomerTableSeeder::class);
        $this->call(CustomerAddressTableSeeder::class);

        $this->call(DriverTableSeeder::class);
        $this->call(MotorTableSeeder::class);
        $this->call(CollectorTableSeeder::class);
    }
}
