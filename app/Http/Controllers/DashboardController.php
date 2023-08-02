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

class DashboardController extends Controller
{

    /** BLOOD BANK DASHBOARD
     * TODO
     * - get hospital count
     * - get count of all orders
     * - get count of blood units
     * - display blood components and how they have been used
     */

    public function BloodBankDashboard()
    {
        try {

            // blood component variables
            $WB = $PRBCs = $FFP = $FP = $PLT = $CRYO = 0;

            // blood groups variables
            $A = $AM = $B = $BM = $AB = $ABM = $O = $OM = 0;

            // Check if user is the blood bank
            if (auth()->user()->role_id != 1) {
                return Result::Error("User is not the blood bank", 400, false);
            }

            // Get new hospital accounts that require verification
            $accounts = User::where('role_id', 2)->where('status_id', 2)->get();

            $hospitalAccountsToVerfiy = [];

            foreach ($accounts as $account) {
                $item = [
                    'msg' => $account->hospital->name . ' has created an account. Please assess their details',
                    'data' => $account
                ];

                array_push($hospitalAccountsToVerfiy, $item);
            }

            // Get all blood units that have a status of taken
            // group them by their blood components

            $bloodUnits = BloodUnit::where('status_id', 8)->get();

            foreach ($bloodUnits as $bloodUnit) {
                switch ($bloodUnit['blood_product']) {
                    case 1:
                        $WB++;
                        break;
                    case 2:
                        $PRBCs++;
                        break;
                    case 3:
                        $FFP;
                        break;
                    case 4:
                        $FP;
                        break;
                    case 5:
                        $PLT++;
                        break;
                    case 6:
                        $CRYO++;
                        break;
                    default:
                        break;
                }

                switch ($bloodUnit['blood_group']) {
                    case 1:
                        $A++;
                        break;
                    case 2:
                        $AM++;
                        break;
                    case 3:
                        $B++;
                        break;
                    case 4:
                        $BM++;
                        break;
                    case 5:
                        $AB++;
                        break;
                    case 6:
                        $ABM++;
                        break;
                    default:
                        break;
                }
            }

            $barChartGroups = [
                ['groups' => 'A', 'quantity' => $A],
                ['groups' => 'A-', 'quantity' => $AM],
                ['groups' => 'B', 'quantity' => $B],
                ['groups' => 'B-', 'quantity' => $BM],
                ['groups' => 'AB', 'quantity' => $AB],
                ['groups' => 'AB-', 'quantity' => $ABM],
                ['groups' => 'O', 'quantity' => $O],
                ['groups' => 'OM', 'quantity' => $OM],
            ];

            $barChartComponents = [
                ['component' => 'WB', 'quantity' => $WB],
                ['component' => 'PRBCs', 'quantity' => $PRBCs],
                ['component' => 'FFP', 'quantity' => $FFP],
                ['component' => 'FP', 'quantity' => $FP],
                ['component' => 'PLT', 'quantity' => $PLT],
                ['component' => 'CRYO', 'quantity' => $CRYO],
            ];

            $dashboard = [
                'barChartComponents' => $barChartComponents,
                'barChartGroups' => $barChartGroups,
                'hospital' => Hospital::all()->count(),
                'orders' => BulkOrder::where('status_id', 3)->where('approved_by', null)->get()->count() + HospitalStaffBloodOrder::where('status_id', 3)->where('approved_by', null)->get()->count(),
                'blood_units' => BloodUnit::where('date_of_expiry', '>=', date('Y-m-d', time()))->where('status_id', 3)->get()->count(),
                "notifications" => count($hospitalAccountsToVerfiy) == 0 ? null : $hospitalAccountsToVerfiy
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
     * - get number of patients who have recieved money
     * - include notifications for new hospital accounts
     */
    public function HospitalDashboard()
    {
        try {

            // months
            $jan = $feb = $mar = $apr = $may = $jun = $jul = $aug = $sep = $oct = $nov = $dec = 0;

            // genders
            $male = $female = 0;

            // Get hospital details
            $hospital = (auth()->user()->hospital != null ? auth()->user()->hospital :
                auth()->user()->hospitalStaff->hospital);

            if ($hospital == null) {
                return Result::Error("User is neither a hospital nor a hospital staff member", 400, false);
            }

            // Blood group variables
            $A = $AMinus = $B = $BMinus = $AB = $ABMinus = $O = $OMinus = 0;

            // Hospital orders for blood
            $orders = BulkOrder::where('hospital_id', $hospital['id'])->get();

            // get orders which have been approved
            $approvedOrders = [];

            if (auth()->user()->hospitalStaff == null) {
                foreach ($orders->where('status_id', 5) as $order) {

                    $item = [
                        'msg' => 'Acknowledege the delivery of this bulk order',
                        'data' => $order
                    ];

                    array_push($approvedOrders, $item);
                }
            }

            // Get staff blood orders
            $staffBloodOrders = 0;

            foreach (HospitalStaff::where('hospital_id', $hospital['id'])->get() as $staff) {
                $staffBloodOrders += HospitalStaffBloodOrder::where('hospital_staff_id', $staff['id'])->get()->count();
            }

            // Total blood units recieved from blood bank
            $recievedFromBank = 0;

            $recievedFromBank = HospitalInventory::where("hospital_id", $hospital['id'])->get()->count();

            // Hospital inventory
            $hospitalInventory = HospitalInventory::where("hospital_id", $hospital['id'])->get();

            // Get blood units number under respective blood groups
            foreach ($hospitalInventory as $item) {
                $bloodUnit = BloodUnit::where('id', $item['blood_unit'])->first();
                if ($bloodUnit['blood_group'] == 1) {
                    $A += 1;
                } else if ($bloodUnit['blood_group'] == 2) {
                    $AMinus += 1;
                } else if ($bloodUnit['blood_group'] == 3) {
                    $B += 1;
                } else if ($bloodUnit['blood_group'] == 4) {
                    $BMinus += 1;
                } else if ($bloodUnit['blood_group'] == 5) {
                    $AB += 1;
                } else if ($bloodUnit['blood_group'] == 6) {
                    $ABMinus += 1;
                } else if ($bloodUnit['blood_group'] == 7) {
                    $O += 1;
                } else if ($bloodUnit['blood_group'] == 8) {
                    $OMinus += 1;
                }
            }

            $inventorySize = $hospitalInventory->count();

            $patients = HospitalStaffBloodOrder::where('hospital_id', $hospital['id'])->get();

            foreach ($patients as $patient) {
                switch ($patient['patient_gender']) {
                    case 'Male':
                        $male++;
                        break;
                    case 'Female':
                        $female++;
                        break;
                    default:
                        break;
                }

                switch (strtolower(date('M', strtotime($patient['created_at'])))) {
                    case 'jan':
                        $jan++;
                        break;
                    case 'feb':
                        $feb++;
                        break;
                    case 'mar':
                        $mar++;
                        break;
                    case 'apr':
                        $apr++;
                        break;
                    case 'may':
                        $may++;
                        break;
                    case 'jun':
                        $jun++;
                        break;
                    case 'jul':
                        $jul++;
                        break;
                    case 'aug':
                        $aug++;
                        break;
                    case 'sep';
                        $sep++;
                        break;
                    case 'oct':
                        $oct++;
                        break;
                    case 'nov':
                        $nov++;
                        break;
                    case 'dec':
                        $dec++;
                        break;
                    default:
                        break;
                }
            }

            $barChart = [
                ['month' => 'Jan', 'count' => $jan],
                ['month' => 'Feb', 'count' => $feb],
                ['month' => 'Mar', 'count' => $mar],
                ['month' => 'Apr', 'count' => $apr],
                ['month' => 'May', 'count' => $may],
                ['month' => 'Jun', 'count' => $jun],
                ['month' => 'Jul', 'count' => $jul],
                ['month' => 'Aug', 'count' => $aug],
                ['month' => 'Sep', 'count' => $sep],
                ['month' => 'Oct', 'count' => $oct],
                ['month' => 'Nov', 'count' => $nov],
                ['month' => 'Dec', 'count' => $dec],
            ];

            $pieChart = [
                ['id' => 'Female', "label" => 'Female', 'color' => "hsl(104, 70%, 50%)", 'value' => $female],
                ['id' => 'Male', "label" => 'Male', 'color' => "hsl(291, 70%, 50%)", 'value' => $male],
            ];

            $obj = [
                "pieChart" => $pieChart,
                "barChart" => $barChart,
                "hospital" => $hospital,
                "hospital_user" => User::where('id', $hospital['user_id'])->first(),
                "orders" => $orders->count(),
                "staffBloodOrders" => $staffBloodOrders,
                "bloodUnitsFromBank" => $recievedFromBank,
                "hospitalInventory" => $hospitalInventory->count(),
                "blood_groups" => [
                    [
                        "group" => "A",
                        "percentage" => $inventorySize === 0 ? 0 : ($A / $inventorySize) * 100
                    ],
                    [
                        "group" => "A-",
                        "percentage" => $inventorySize === 0 ? 0 : ($AMinus / $inventorySize) * 100
                    ],
                    [
                        "group" => "B",
                        "percentage" => $inventorySize === 0 ? 0 : ($B / $inventorySize) * 100
                    ],
                    [
                        "group" => "B-",
                        "percentage" => $inventorySize === 0 ? 0 : ($BMinus / $inventorySize) * 100
                    ],
                    [
                        "group" => "AB",
                        "percentage" => $inventorySize === 0 ? 0 : ($AB / $inventorySize) * 100,
                    ],
                    [
                        "group" => "AB-",
                        "percentage" => $inventorySize === 0 ? 0 : ($ABMinus / $inventorySize) * 100,
                    ],
                    [
                        "group" => "O",
                        "percentage" => $inventorySize === 0 ? 0 : ($O / $inventorySize) * 100
                    ],
                    [
                        "group" => "O-",
                        "percentage" => $inventorySize === 0 ? 0 : ($OMinus * $inventorySize) * 100
                    ]
                ],
                "notifications" => count($approvedOrders) == 0 ? null : $approvedOrders
            ];

            return Result::ReturnObject($obj, 200, "Hospital dashboard", true);
        } catch (\Exception $exp) {
            Log::error($exp->getMessage());
            return Result::Error("Service Temporarily down", 500, false);
        }
    }
}
