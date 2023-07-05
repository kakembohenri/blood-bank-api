<?php

namespace App\Http\Controllers;

use App\CustomHelper\Result;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HospitalStaffController extends Controller
{
    // Create hospital staff
    public function createHospitalStaff(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|unique:users',
                'phone' => 'required|min:10|max:10',
                'name' => 'required|string',
                'role_id' => 'required|integer'
            ]);
        } catch (\Exception $exp) {
            $result = Result::Error($exp->getMessage(), 400);
            return $result;
        }
        try {
            DB::beginTransaction();

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
                'name' => $request->name,
                'hospital_id' => auth()->user()->hospital->id,
                'created_at' => date('Y:m:d H:i:s', time()),
                'created_by' => auth()->user()->id
            ]);

            DB::commit();

            return Result::ReturnMessage('New Hospital staff has been created', 201);
        } catch (\Exception $exp) {
            DB::rollBack();

            $result = Result::Error($exp->getMessage(), 500);
            return $result;
        }
    }

    // Update hospital staff
    public function updateHospitalStaff(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|integer',
                'email' => 'required|email',
                'phone' => 'required|min:10|max:10',
                'name' => 'required|string',
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

            // Check whether new email is already taken
            $otherUsersEmail = User::where('email', $request->email)->first();

            if ($otherUsersEmail != null) {
                if ($otherUsersEmail->id != $request->user_id) {
                    return Result::Error('Email address already taken', 400);
                }
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
            $user->hospitalStaff()->update([
                'name' => $request->name,
                'hospital_id' => auth()->user()->hospital->id,
                'modified_at' => date('Y:m:d H:i:s', time()),
                'modified_by' => auth()->user()->id
            ]);

            DB::commit();

            return Result::ReturnMessage('Hospital staff has been updated', 204);
        } catch (\Exception $exp) {
            DB::rollBack();
            $result = Result::Error($exp->getMessage(), 500);
            return $result;
        }
    }
}
