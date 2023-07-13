<?php

namespace App\Http\Controllers;

use App\CustomHelper\Result;
use App\Models\HospitalStaff;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule as ValidationRule;

class HospitalStaffController extends Controller
{
    /** GET HOSPITAL STAFF
     * ENDPOINT: /hospital-staff
     * METHOD: GET
     * TODO
     * - get hospital staff for logged in hospital user
     */

    public function GetHospitalStaff()
    {
        try {
            // Check if user is hospital
            $hospital = auth()->user()->hospital;

            if ($hospital == null) {
                return Result::Error('User is not a hospital!', 400, false);
            }

            // Get hospital staff
            $hospitalStaff = HospitalStaff::where('hospital_id', auth()->user()->hospital->id)->get();

            $staffArray = [];

            foreach ($hospitalStaff as $staff) {
                $item['id'] = $staff->user_id;
                $item['name'] = $staff->name;
                $item['email'] = $staff->user->email;
                $item['phone'] = $staff->user->phone;
                $item['position'] = $staff->user->role->name;

                array_push($staffArray, $item);
            }

            return Result::ReturnList($staffArray, 200, 'Ok', true);
        } catch (\Exception $exp) {
            Log::error("Fetching hospital staff error: " . $exp->getMessage());
            return Result::Error("Service temporarily down", 500, false);
        }
    }

    /** CREATE HOSPITAL STAFF
     * ENDPOINT: /hospital-staff
     * METHOD: POST
     * TODO
     * - create a user 
     * - add new user in the hospital staff table
     */
    public function CreateHospitalStaff(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|unique:users',
                'phone' => 'required|min:10|max:10|unique:users',
                'first_name' => 'required|string',
                'middle_name' => 'nullable|string',
                'last_name' => 'required|string',
                'role_id' => 'required|integer'
            ]);
        } catch (\Exception $exp) {
            $result = Result::Error($exp->getMessage(), 400, false);
            return response()->json($result, 400);
        }
        try {
            DB::beginTransaction();

            switch ($request->role_id) {
                case 1:
                    return Result::ReturnMessage("Role not for hospital staff", 400, false);
                case 2:
                    return Result::ReturnMessage("Role not for hospital staff", 400, false);
                default:
                    break;
            }

            if (Role::where('id', $request->role_id)->first() == null) {
                return Result::ReturnMessage("Role does not exist", 400, false);
            }

            //Create new user
            $user = User::create([
                'email' => $request->email,
                'phone' => $request->phone,
                'role_id' => $request->role_id,
                'status_id' => 1,
                'password' => md5('123456'),
                'created_at' => date('Y:m:d H:i:s', time()),
                'created_by' => auth()->user()->id
            ]);

            // Create new hospital staff
            $user->hospitalStaff()->create([
                'name' => $request->first_name . " " . $request->middle_name . " " . $request->last_name,
                'hospital_id' => auth()->user()->hospital->id,
                'created_at' => date('Y:m:d H:i:s', time()),
                'created_by' => auth()->user()->id
            ]);

            DB::commit();

            return Result::ReturnMessage('New Hospital staff has been created', 201, true);
        } catch (\Exception $exp) {
            DB::rollBack();
            Log::error($exp->getMessage());
            $result = Result::Error('Service Temporarily Down', 500);
            return $result;
        }
    }

    /** UPDATE HOSPITAL STAFF
     * ENDPOINT: /hospital-staff
     * METHOD: PUT
     * TODO
     * - update user asscociated with this hospital staff
     * - eventually update the hospital staff
     * 
     */
    public function UpdateHospitalStaff(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|integer',
                'email' => ['required', ValidationRule::unique('users')->ignore(User::where('id', $request->user_id)->first()), 'email', 'max:50'],
                'phone' => ['required', ValidationRule::unique('users')->ignore(User::where('id', $request->user_id)->first()),  'min:10', 'max:10'],
                'first_name' => 'required|string',
                'middle_name' => 'sometimes|required|string',
                'last_name' => 'required|string',
                'role_id' => 'required|integer'
            ]);
        } catch (\Exception $exp) {
            $result = Result::Error($exp->getMessage(), 400);
            return $result;
        }

        try {
            DB::beginTransaction();

            if (User::where('id', $request->user_id)->first() == null) {
                return Result::Error('User does not exist', 400);
            }

            //Update user
            $user = User::where('id', $request->user_id)->update([
                'email' => $request->email,
                'phone' => $request->phone,
                'role_id' => $request->role_id,
                'modified_at' => date('Y:m:d H:i:s', time()),
                'modified_by' => auth()->user()->id
            ]);

            // Update hospital staff
            HospitalStaff::where('user_id', $request->user_id)->update([
                'name' => $request->first_name . " " . $request->middle_name . " " . $request->last_name,
                'hospital_id' => auth()->user()->hospital->id,
                'modified_at' => date('Y:m:d H:i:s', time()),
                'modified_by' => auth()->user()->id
            ]);

            DB::commit();

            return Result::ReturnMessage('Hospital staff has been updated', 204, true);
        } catch (\Exception $exp) {
            DB::rollBack();
            Log::error($exp->getMessage());
            return Result::Error("Service Temporarily Down", 500);
        }
    }
}
