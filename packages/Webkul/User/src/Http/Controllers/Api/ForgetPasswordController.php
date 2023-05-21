<?php

namespace Webkul\User\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Password;
use Tymon\JWTAuth\Facades\JWTAuth;
use Webkul\User\Http\Controllers\Controller;
use Webkul\User\Http\Requests\UserForgetPasswordCheckEmailRequest;
use Webkul\User\Http\Requests\UserForgetPaswordCheckOTP;
use Webkul\User\Http\Requests\UserForgetPaswordCheckOTPRequest;
use Webkul\User\Http\Requests\UserForgetPaswordResetRequest;
use Webkul\User\Repositories\AdminRepository;

class ForgetPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    protected  $adminRepository;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(AdminRepository $adminRepository)
    {
        $this->_config = request('_config');
        $this->adminRepository=$adminRepository;
    }

    public function checkEmail(UserForgetPasswordCheckEmailRequest $request){
        $this->adminRepository->forgetPassswordCheckEmail($request->only('email'));
        return responder()->success() ;
    }

    public function checkOTP(UserForgetPaswordCheckOTPRequest $request){
        $data=$request->only('email','otp');
        $dateAllowed=$this->adminRepository->forgetPassswordCheckOTP($data);
        if($dateAllowed)
            return responder()->success(['message'=>'Pin Code is valid!']) ;
        else
            return responder()->error(400,'Pin Codee is Expired!')->respond(400) ;
    }

    public function resetPassword(UserForgetPaswordResetRequest $request){
        $data=$request->only('email','otp','password');
        $user=$this->adminRepository->forgetPassswordReset($data);
        $token = JWTAuth::fromUser($user);
        return responder()->success();

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        if (auth()->guard('admin')->check()) {
            return redirect()->route('admin.dashboard.overview.index');
        } else {
            if (strpos(url()->previous(), 'admin') !== false) {
                $intendedUrl = url()->previous();
            } else {
                $intendedUrl = route('admin.dashboard.overview.index');
            }

            session()->put('url.intended', $intendedUrl);

            return view($this->_config['view']);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        try {
            $this->validate(request(), [
                'email' => 'required|email',
            ]);

            $response = $this->broker()->sendResetLink(
                request(['email'])
            );

            if ($response == Password::RESET_LINK_SENT) {
                session()->flash('success', trans($response));

                return back();
            }

            return back()
                ->withInput(request(['email']))
                ->withErrors([
                    'email' => trans($response),
                ]);
        } catch(\Exception $e) {
            session()->flash('error', trans($e->getMessage()));

            return redirect()->back();
        }
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker('admins');
    }
}