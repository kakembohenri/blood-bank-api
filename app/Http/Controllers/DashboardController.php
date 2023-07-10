<?php

namespace App\Http\Controllers;

use App\CustomHelper\Result;
use App\Models\BloodGroup;
use App\Models\BloodUnit;
use App\Models\BulkOrder;
use App\Models\BulkOrderItem;
use App\Models\Hospital;
use App\Models\HospitalInventory;
use App\Models\HospitalStaff;
use App\Models\HospitalStaffBloodOrder;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Stmt\Return_;

class DashboardController extends Controller
{

    /** BLOOD BANK DASHBOARD
     * TODO
     * - get hospital count
     * - get count of all orders
     * - get count of blood units
     * Wait for clarification on others
     */

    public function BloodBankDashboard()
    {
        try {


            // Check if user is the blood bank
            if (auth()->user()->role_id != 1) {
                return Result::Error("User is not the blood bank", 400, false);
            }

            $dashboard = [
                'hospital' => Hospital::all()->count(),
                'orders' => BulkOrder::where('status_id', 3)->where('approved_by', null)->get()->count() + HospitalStaffBloodOrder::where('status_id', 3)->where('approved_by', null)->get()->count(),
                'blood_units' => BloodUnit::where('date_of_expiry', '>=', date('Y-m-d', time()))->where('status_id', 3)->get()->count()
            ];

            return Result::ReturnObject($dashboard, 200, "Blood Bank Dashboard", true);
        } catch (\Exception $exp) {
            Log::error($exp->getMessage());
            return Result::Error("Service Temporarily down", 500, false);
        }
    }

    /** HOSPITAL DASHBOARD
     * TODO
     * - get hospital details
     * - hospital orders for blood
     * - staff blood orders
     * - total blood units recieved from the blood bank
     * - hospital inventory
     * 
     */
    public function HospitalDashboard()
    {
        try {

            // Get hospital details
            $hospital = (auth()->user()->hospital != null ? auth()->user()->hospital :
                auth()->user()->hospitalStaff);

            if ($hospital == null) {
                return Result::Error("User is neither a hospital nor a hospital staff member", 400, false);
            }

            // Blood group variables
            $A = $AMinus = $B = $BMinus = $AB = $ABMinus = $O = $OMinus = 0;

            // Hospital orders for blood
            $orders = BulkOrder::where('hospital_id', $hospital['id'])->get();

            // Get staff blood orders
            $staffBloodOrders = 0;

            foreach (HospitalStaff::where('hospital_id', $hospital['id'])->get() as $staff) {
                $staffBloodOrders += HospitalStaffBloodOrder::where('hospital_staff', $staff['id'])->get()->count();
            }
            // HospitalStaffBloodOrder::where('hospital_id', $hospital['id'])->get()->count();

            // Total blood units recieved from blood bank
            $bloodUnits = 0;

            foreach ($orders as $order) {
                $bloodUnits += BulkOrderItem::where("bulk_order", $order['id'])->get()->count();
            }

            // Hospital inventory
            $hospitalInventory = HospitalInventory::where("hospital_id", $hospital['id'])->get();

            // Get blood units number under respective blood groups
            // foreach (BloodGroup::all() as $bloodGroup) {
            //     // Loop through inventory to get blood unit matching blood product
            //     foreach ($hospitalInventory as $item) {
            //         $bloodUnit = BloodUnit::where('id', $item['blood_unit'])->first();
            //         if ($bloodUnit['blood_group'] == 1) {
            //             $A += 1;
            //         } else if ($bloodUnit['blood_group'] == 2) {
            //             $AMinus += 1;
            //         } else if ($bloodUnit['blood_group'] == 3) {
            //             $B += 1;
            //         } else if ($bloodUnit['blood_group'] == 4) {
            //             $BMinus += 1;
            //         } else if ($bloodUnit['blood_group'] == 5) {
            //             $AB += 1;
            //         } else if ($bloodUnit['blood_group'] == 6) {
            //             $ABMinus += 1;
            //         } else if ($bloodUnit['blood_group'] == 7) {
            //             $O += 1;
            //         } else if ($bloodUnit['blood_group'] == 8) {
            //             $OMinus += 1;
            //         }
            //     }
            // }

            $obj = [
                "hospital" => $hospital,
                "hospital_user" => User::where('id', $hospital['user_id'])->first(),
                "orders" => $orders->count(),
                "staffBloodOrders" => $staffBloodOrders,
                "bloodUnitsFromBank" => $bloodUnits,
                "hospitalInventory" => $hospitalInventory->count(),
                // "blood_groups" => [
                //     "A" => $A,
                //     "A-" => $AMinus,
                //     "B" => $B,
                //     "B-" => $BMinus,
                //     "AB" => $AB,
                //     "AB-" => $ABMinus,
                //     "O" => $O,
                //     "O-" => $OMinus,
                // ]
            ];

            return Result::ReturnObject($obj, 200, "Hospital dashboard", true);
        } catch (\Exception $exp) {
            Log::error($exp->getMessage());
            return Result::Error("Service Temporarily down", 500, false);
        }
    }
}
