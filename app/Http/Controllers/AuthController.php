<?php

namespace App\Http\Controllers;

use App\CustomHelper\Result;
use App\Mail\AcceptHospital;
use App\Mail\RejectHospital;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
                $result = Result::Error("Invalid Credentials", 400);

                return $result;
            }

            // Check status of user
            switch ($userExists['status_id']) {
                case 2:
                    return Result::Error('Your account has not yet been verified', 400, false);
                case 7:
                    return Result::Error('Your account was rejected! Get you blood from else where', 400, false);
                default:
                    break;
            }
            $token = $userExists->createToken('myApp')->plainTextToken;

            $returnObject = [
                "token" => $token,
                "user" => $userExists,
                "hospital" => $userExists->hospital
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
     * - send email notifying the hospital that their account has been accepted
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

            try {
                Mail::to($user['email'])->send(new AcceptHospital());
            } catch (\Exception $exp) {
                Log::error($exp->getMessage());
                return Result::Error('Email could not be sent', 400);
            }

            // Update hospital users status from unverified to verified
            $user->update(['status_id' => 1]);

            return Result::ReturnMessage("Hospital account successfully verified", 200, true);
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


            try {
                Mail::to($user['email'])->send(new RejectHospital());
            } catch (\Exception $exp) {
                Log::error($exp->getMessage());
                return Result::Error('Email could not be sent', 400);
            }

            // Update hospital users status from unverified to rejected
            $user->update(['status_id' => 7]);

            return Result::ReturnMessage("Hospital account successfully rejected", 200, true);
        } catch (\Exception $exp) {
            Log::error($exp->getMessage());
            return Result::Error("Service Temporarily Unavailable", 500, false);
        }
    }
}
