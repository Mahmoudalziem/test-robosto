<?php

namespace Webkul\User\Repositories;

use Illuminate\Support\Facades\Event;
use Tymon\JWTAuth\Facades\JWTAuth;
use Webkul\Core\Eloquent\Repository;
use Webkul\User\Mail\ForgetPasswordOTPMail;
use Illuminate\Support\Facades\Mail;
use Webkul\User\Models\Admin;

class AdminRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'Webkul\User\Contracts\Admin';
    }


    public function forgetPassswordCheckEmail($data){
        $email=$data['email'];
        $user=$this->findOneByField('email',$email);

        // save OTP to Admin Table
        $OTP=$this->generateOtp();
        $user->update(["otp"=>$OTP]);
        // send email with OTP

        return $OTP;
    }

    public function forgetPassswordCheckOTP($data){
       $isEexpiredDate= ! Admin::where('email',$data['email'])->whereBetween('updated_at', [now()->subMinutes(1), now()])->exists();

       // updated at should not be more than 3 mins else OTP is expired
       if($isEexpiredDate){
           return false; // send message tell user his OTP is expired
       }
        return true;
    }

    public function forgetPassswordReset($data){
        $user=$this->findWhere(['email'=>$data['email'],'otp'=>$data['otp']])->first();
        $user->update(['password'=> bcrypt($data['password'])]);
        return $user;
    }


    public function generateOtp(){
        $otp = mt_rand(100000,999999);
        return $otp;
    }
}