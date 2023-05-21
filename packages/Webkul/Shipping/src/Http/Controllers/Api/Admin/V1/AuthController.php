<?php

namespace Webkul\Shipping\Http\Controllers\Api\Admin\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Webkul\Shipping\Http\Requests\LoginRequest;
use Webkul\Shipping\Http\Resources\AuthResource;
use Webkul\Core\Http\Controllers\BackendBaseController;

class AuthController extends BackendBaseController
{

    protected $guard = 'shipper';

    /**
     * Login The Shipper.
     *
     * @return \Illuminate\Http\Response
     */
    public function login(LoginRequest $request)
    {

        if (!$jwtToken = auth($this->guard)->attempt($request->only('email', 'password'))) {
            return $this->responseError(401, "البريد الالكتروني او كلمة المرور غير صحيحه");
        }

        return $this->responseSuccess(
            [
                'user' => new AuthResource(auth($this->guard)->user()),
                'access_token' => $jwtToken
            ]
        );
    }



    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function me(Request $request)
    {
        return $this->responseSuccess([
            'user' => new AuthResource(auth($this->guard)->user()),
        ]);
    }
}
