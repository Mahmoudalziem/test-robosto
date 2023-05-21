<?php
namespace Webkul\Core\Services\Payment;

interface Payment
{
    public function addNewCard(array $data);
    public function resendOtp(array $data);
    public function validateOTP(array $data);
    public function customerPaymentInfo(string $userId);
    public function customerCardInfo(string $userId, string $cardId);
    public function customerTransactionsList(array $data);
    public function customerTransactionInfo(string $transactionId);
    public function customerTransactionStatus(array $data);
}