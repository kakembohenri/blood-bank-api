<?php

namespace App\Http\Controllers;

use App\CustomHelper\Result;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HospitalController extends Controller
{
    /** CREATE HOSPITAL ACCOUNT
     * 
     */
    public function createAccount(Request $request)
    {
        try {

            try {
                $request->validate([
                    'email' => 'required|email|unique:users',
                    'name' => 'required|string',
                    'phone' => 'required|string|min:10|max:10|unique:users',
                    'location' => 'required|string',
                    'FacilityCode' => 'required|string',
                    'District' => 'required|string',
                    'password' => 'required|confirmed|min:6',
                    // 'password_confirmation' => 'required|min:6'
                ], [
                    'email.required' => 'Email address Field is required!',
                    'name.required' => 'Name Field is required!',
                    'location.required' => 'Location Field is required!',
                    'password.required' => 'Password Field is required!',
                    'password.confirm' => 'Passwords do not match!',
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $result = Result::Error($e->validator->errors(), 400, false);

                return $result;
            }

            DB::beginTransaction();

            $newPassword = md5($request->password);

            // TODO: Verify email address exists

            // Create user
            $user = User::create([
                'email' => $request->email,
                'password' => $newPassword,
                'phone' => $request->phone,
                'status_id' => 2,
                'role_id' => 2,
                'created_at' => date('Y:m:d H:i:s', time())
            ]);

            // Add new user to hospital
            Hospital::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'location' => $request->location,
                'FacilityCode' => $request->FacilityCode,
                'District' => $request->District,
                'created_at' => date('Y:m:d H:i:s', time()),
                'created_by' => $user->id,
            ]);

            DB::commit();

            return Result::ReturnMessage("Account successfully created. Please wait for account verification by the blood bank", 201, true);
        } catch (\Exception $exp) {
            DB::rollBack();

            $result = Result::Error($exp->getMessage(), 400, false);

            return $result;
        }
    }

    /** GET HOSPITAL VIA TOKEN
     * DESCRIPTION: Get a logged in users hospital details
     * ENDPOINT: /hospital-details
     * METHOD: GET
     * - get hospital details via logged in user id
     */

    public function HospitalDetails($hospitalId)
    {
        try {
            return Result::ReturnObject(['user' => auth()->user(), 'hospital' => Hospital::where('id', $hospitalId)->first()], 200, 'Ok');
        } catch (\Exception $exp) {
            Log::error($exp->getMessage());
            return Result::Error('Service Temporarily Unavailable', 500);
        }
    }

    /** GET HOSPITAL BY LOOGED IN USER ID
     * DESCRIPTION: Responsible for getting a hospital via the logged in user id
     * ENDPOINT: /hospital
     * METHOD: GET
     * TODO
     * - get hospital with matching user id to the logged in users
     */

    public function GetHospitalByUserId()
    {
    }
}
