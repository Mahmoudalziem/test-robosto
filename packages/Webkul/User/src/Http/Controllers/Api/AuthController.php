<?php

namespace Webkul\User\Http\Controllers\Api;


use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Event;
use Webkul\User\Http\Controllers\Controller;
use Webkul\User\Http\Requests\UserLoginRequest;
use Webkul\Admin\Http\Resources\User\AdminSingle;
use Webkul\Core\Http\Controllers\BackendBaseController;

class AuthController extends BackendBaseController
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected  $guard;
    public function __construct()
    {
        $this->guard = 'admin';

        auth()->setDefaultDriver($this->guard);
    }



    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function login(UserLoginRequest $request)
    {
          
        $jwtToken = null;
 
        if (! $jwtToken = auth()->guard($this->guard)->attempt($request->only('email', 'password'))) {
            return $this->responseError(422,'invalid-email-or-password');            
        }
        $admin = auth($this->guard)->user();
        // save firebase token(device token)
        if ($request->fcm_token){
            $admin->deviceToken()->delete();
            $admin->deviceToken()->create(['token'=>$request->fcm_token]);
        }

        Event::dispatch('user.after.login', request('email'));
        return $this->responseSuccess($this->createNewToken($admin));
      
    }


    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {

        auth()->guard($this->guard)->logout();
        return response()->json(['message' => 'User successfully signed out']);
    }
    
    public function me() {
  

        $admin=auth()->guard($this->guard)->user();
        $data= new AdminSingle($admin);
         return $this->responseSuccess($data);
    }
    
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {

        return responder()->success([
            'access_token' => auth()->refresh(),
            'token_type' => 'bearer',
            'expires_in' => (int) config('jwt.ttl') ,
            'user' => auth($this->guard)->user()
        ]);
    }


    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return array
     */
    protected function createNewToken($admin){
        $token = JWTAuth::fromUser($admin);
        $response=[
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' =>  (int) config('jwt.ttl'),
            'user' => new AdminSingle(auth($this->guard)->user()),
            
        ];
        return $response;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        auth()->guard('admin')->logout();

        return redirect()->route($this->_config['redirect']);
    }


}