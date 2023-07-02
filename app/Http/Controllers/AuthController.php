<?php

namespace App\Http\Controllers;

use App\CustomHelper\Result;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {

            try {
                $request->validate([
                    'email' => 'required|email',
                    'password' => 'required'
                ], [
                    'email.required' => 'Email address Field is required!',
                    'password.required' => 'Password Field is required!'
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $result = Result::Error($e->validator->errors(), 400);
                return $result;
            }

            $newPassword = md5($request->password);

            $userExists = User::where('password', $newPassword)->where('email', $request->email)->first();

            if ($userExists == null) {
                $result = Result::ReturnMessage("Invalid Credentials", 400);

                return $result;
            }


            $token = $userExists->createToken('myApp')->plainTextToken;

            $returnObject = [
                "token" => $token,
                "user" => $userExists
            ];

            $result = Result::ReturnObject($returnObject, 200, "Login succesfull");

            return $result;
        } catch (\Exception $exp) {

            $result = Result::Error($exp->getMessage(), 400);

            return $result;
        }
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();
        return Result::ReturnMessage("Logged out", 200);
    }
}
