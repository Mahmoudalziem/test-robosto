<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Webkul\Customer\Services\SMS\VictoryLink\SendSMS;

if (!function_exists('logOrderActionsInCache')) {

    function logOrderActionsInCache(int $orderId, string $action) {
        // Get Old Logs
        $oldLogs = Cache::get("order_{$orderId}_log");

        // if no old logs, then the first time
        if (!$oldLogs) {
            Cache::put("order_{$orderId}_log", [
                "{$action}" => now()->format('Y-m-d H:i:s'),
            ]);
        } else {
            // Append new action to old Logs
            $oldLogs["{$action}"] = now()->format('Y-m-d H:i:s');

            Cache::put("order_{$orderId}_log", $oldLogs);
        }
    }

}


if (!function_exists('logProductStockInCache')) {

    function logProductStockInCache(int $orderId, int $productId, string $title, int $qty) {
        // Get Old Logs
        $oldLogs = Cache::get("order_{$orderId}_product_{$productId}_log");

        // if no old logs, then the first time
        if (!$oldLogs) {
            Cache::put("order_{$orderId}_product_{$productId}_log", [
                "{$title}" => $qty,
            ]);
        } else {
            // Append new qty to old Logs
            $oldLogs["{$title}"] = $qty;
            Cache::put("order_{$orderId}_product_{$productId}_log", $oldLogs);
        }
    }

}

if (!function_exists('logAdjustmentStockInCache')) {

    function logAdjustmentStockInCache(int $adjustmentId, int $productId, string $title, array $qty) {
        // Get Old Logs
        $oldLogs = Cache::get("adjustment_stock_problem_product_{$productId}_log");

        // if no old logs, then the first time
        if (!$oldLogs) {
            Cache::put("adjustment_stock_problem_product_{$productId}_log", [
                "{$title}" => $qty,
            ]);
        } else {
            // Append new qty to old Logs
            $oldLogs["{$title}"] = $qty;
            Cache::put("adjustment_stock_problem_product_{$productId}_log", $oldLogs);
        }
    }

}


if (!function_exists('sendSMSToDevTeam')) {

    function sendSMSToDevTeam($msg = null) {
        $text = config('app.env');
        $text .= $msg != null ? $msg : ": ياجلال في مشكلة حصلت في السيرفر. في الريديس او جازل";

        $lang = 'ar';
        $sender = 'Robosto';
        $sms = new SendSMS('01091447677', $text, $lang, $sender);

        return $sms->send();
    }

}
