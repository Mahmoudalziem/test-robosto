<?php

use Illuminate\Database\Seeder;
use Webkul\Core\Database\Seeders\TagTableSeeder;
use Webkul\Area\Database\Seeders\AreaTableSeeder;
use Webkul\User\Database\Seeders\RolesTableSeeder;
use Webkul\Brand\Database\Seeders\BrandTableSeeder;
use Webkul\Core\Database\Seeders\ShelveTableSeeder;
use Webkul\User\Database\Seeders\AdminsTableSeeder;
use Webkul\Core\Database\Seeders\ChannelTableSeeder;
use Webkul\Core\Database\Seeders\LocalesTableSeeder;
use Webkul\Driver\Database\Seeders\MotorTableSeeder;
use Webkul\Product\Database\Seeders\UnitTableSeeder;
use Webkul\Banner\Database\Seeders\BannerTableSeeder;
use Webkul\Core\Database\Seeders\SettingsTableSeeder;
use Webkul\Driver\Database\Seeders\DriverTableSeeder;
use Webkul\Sales\Database\Seeders\PaymentTableSeeder;
use Webkul\Core\Database\Seeders\CountriesTableSeeder;
use Webkul\Customer\Database\Seeders\AvatarTableSeeder;
use Webkul\Product\Database\Seeders\ProductTableSeeder;
use Webkul\Category\Database\Seeders\CategoryTableSeeder;
use Webkul\Customer\Database\Seeders\CustomerTableSeeder;
use Webkul\Supplier\Database\Seeders\SupplierTableSeeder;
use Webkul\Collector\Database\Seeders\CollectorTableSeeder;
use Webkul\Inventory\Database\Seeders\InventoryTableSeeder;
use Webkul\Inventory\Database\Seeders\WarehouseTableSeeder;
use Webkul\Customer\Database\Seeders\AddressIconTableSeeder;
use Webkul\Customer\Database\Seeders\CustomerAddressTableSeeder;
use Webkul\Customer\Database\Seeders\CustomerSettingsTableSeeder;

class TestDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Core
        $this->call(AdminsTableSeeder::class);
        $this->call(RolesTableSeeder::class);
        $this->call(CountriesTableSeeder::class);
        $this->call(SettingsTableSeeder::class);
        $this->call(LocalesTableSeeder::class);
        $this->call(ChannelTableSeeder::class);
        $this->call(TagTableSeeder::class);
        $this->call(PaymentTableSeeder::class);

        // Inventory
        $this->call(AreaTableSeeder::class);
        $this->call(WarehouseTableSeeder::class);
        $this->call(SupplierTableSeeder::class);
        // Driver
        $this->call(DriverTableSeeder::class);
        $this->call(MotorTableSeeder::class);
        $this->call(CollectorTableSeeder::class);
        // Customer
        $this->call(AddressIconTableSeeder::class);
        $this->call(AvatarTableSeeder::class);
        $this->call(CustomerTableSeeder::class);
        $this->call(CustomerAddressTableSeeder::class);
        $this->call(CustomerSettingsTableSeeder::class);
        
        $this->call(BannerTableSeeder::class);

        // Products
        $this->call(BrandTableSeeder::class);
        $this->call(CategoryTableSeeder::class);
        $this->call(UnitTableSeeder::class);
        $this->call(ShelveTableSeeder::class);
        $this->call(ProductTableSeeder::class);
        $this->call(InventoryTableSeeder::class);
    }
}
