<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Http\Controllers\Auth\SMSTrait;

class SendTargetedSMS extends Command
{
    use SMSTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:sms {text}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send SMS to Customers';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('###############################################');
        $this->info('Start Send SMS');

        // $customers = Customer::select('name', 'phone')->get()->toArray();
        $text = $this->argument('text');
        $ids = [
            26,
            27,
            28,
            31,
            32,
            34,
            37,
            38,
            42,
            43,
            58,
            59,
            69,
            70,
            81,
            83,
            85,
            146,
            147,
            313,
            314,
            339,
            388,
            394,
            401,
            406,
            496,
            509,
            512,
            545,
            637,
            638,
            698,
            706,
            715,
            735,
            824,
            860,
            873,
            900,
            928,
            988,
            996,
            1071,
            1089,
            1112,
            1128,
            1140,
            1160,
            1174,
            1219,
            1224,
            1231,
            1245,
            1287,
            1290,
            1305,
            1316,
            1317,
            1335,
            1367,
            1372,
            1374,
            1392,
            1393,
            1405,
            1406,
            1416,
            1421,
            1428,
            1433,
            1445,
            1456,
            1460,
            1475,
            1476,
            1481,
            1491,
            1492,
            1494,
            1500,
            1505,
            1512,
            1521,
            1522,
            1546,
            1564,
            1565,
            1568,
            1591,
            1598,
            1602,
            1607,
            1608,
            1623,
            1628,
            1631,
            1632,
            1638,
            1646,
            1647,
            1648,
            1649,
            1676,
            1693,
            1699,
            1702,
            1706,
            1716,
            1717,
            1719,
            1721,
            1722,
            1730,
            1735,
            1766,
            1782,
            1788,
            1792,
            1799,
            1804,
            1814,
            1819,
            1870,
            1882,
            1895,
            1903,
            1910,
            1914,
            1935,
            1995,
            2016,
            2023,
            2036,
            2047,
            2063,
            2073,
            2103,
            2105,
            2115,
            2118,
            2128,
            2135,
            2172,
            2192,
            2202,
            2220,
            2228,
            2229,
            2232,
            2258,
            2271,
        ];

        Customer::whereIn('id', $ids)->chunk(20, function ($customers) use ($text) {

            foreach ($customers as $customer) {
                // Get Customer Name
                $name = explode(' ', $customer['name'])[0];

                // Handle Message
                $message = str_replace('{name}', $name, $text);

                $this->sendSMS($customer['phone'], $message);

                $this->info('SMS Was Sent to ' . $customer['id']);
            }
        });



        $this->info('SMS Was Sent');
        $this->info('###############################################');
    }
}
