<?php

namespace App\Http\Controllers;

use App\CustomHelper\Result;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
                $result = Result::Error($e->validator->errors(), 400, false);
                return $result;
            }

            $newPassword = md5($request->password);

            $userExists = User::where('password', $newPassword)->where('email', $request->email)->first();

            if ($userExists == null) {
                $result = Result::ReturnMessage("Invalid Credentials", 400, false);

                return $result;
            }


            $token = $userExists->createToken('myApp')->plainTextToken;

            $returnObject = [
                "token" => $token,
                "user" => $userExists
            ];

            $result = Result::ReturnObject($returnObject, 200, "Login succesfull", true);

            return $result;
        } catch (\Exception $exp) {

            $result = Result::Error($exp->getMessage(), 400, false);

            return $result;
        }
    }

    public function logout()
    {
        try {
            auth()->user()->tokens()->delete();
            return Result::ReturnMessage("Logged out", 200, true);
        } catch (\Exception $exp) {
            Log::Error($exp->getMessage());
            return Result::Error("Logout Operation failed", 500, false);
        }
    }

    /** VERIFY HOSPITAL ACCOUNT
     * ENDPOINT: /verify-hospital
     * METHOD: PUT
     * TODO
     * - update user status id to that of verified which is 1
     */
    public function VerifyHospital($id)
    {
        try {
            // Check if user exists
            $user = User::where('id', $id)->first();

            if ($user == null) {
                return Result::ReturnMessage("User does not exists", 400, false);
            }

            // Update hospital users status from unverified to verified
            $user->update(['status_id' => 1]);

            return Result::ReturnMessage("Hospital account successfully verified", 204, true);
        } catch (\Exception $exp) {
            Log::error($exp->getMessage());
            return Result::Error("Service Temporarily Unavailable", 500, false);
        }
    }

    /** REJECT HOSPITAL ACCOUNT
     * ENDPOINT: /reject-hospital
     * METHOD: PUT
     * TODO
     * - update user status id to that of rejected which is 7
     */
    public function RejectHospital($id)
    {
        try {
            // Check if user exists
            $user = User::where('id', $id)->first();

            if ($user == null) {
                return Result::ReturnMessage("User does not exists", 400, false);
            }

            // Update hospital users status from unverified to rejected
            $user->update(['status_id' => 7]);

            return Result::ReturnMessage("Hospital account successfully rejected", 204, true);
        } catch (\Exception $exp) {
            Log::error($exp->getMessage());
            return Result::Error("Service Temporarily Unavailable", 500, false);
        }
    }
}
