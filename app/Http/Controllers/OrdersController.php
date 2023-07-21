<?php

namespace App\Http\Controllers;

use App\CustomHelper\Result;
use App\Models\BloodGroup;
use App\Models\BloodProduct;
use App\Models\BloodUnit;
use App\Models\BulkOrder;
use App\Models\BulkOrderItem;
use App\Models\Hospital;
use App\Models\HospitalInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrdersController extends Controller
{
    // Create bulk order items
    protected function CreateBulkOrderItems($bulkOrderItem, $bulkOrder)
    {

        // Get blood units
        $bloodUnits = BloodUnit::where('blood_group', $bulkOrderItem['blood_group'])->where('blood_product', $bulkOrderItem['blood_product'])->where('status_id', 3)->where('date_of_expiry', '>=', date('Y-m-d', time()))
            ->orderByDesc('date_of_expiry')->get();

        return $bloodUnits;

        // Check if quantity of blood units dont exceed available limit
        if ($bulkOrderItem['quantity'] > $bloodUnits->count()) {
            DB::rollBack();
            $msg = 'Blood units of blood group: ' . BloodGroup::where('id', $bulkOrderItem['blood_group'])->select('name')->first() .
                ',component: ' . BloodProduct::where('id', $bulkOrderItem['blood_product'])->select('name')->first() . ' and quantity ' .
                $bulkOrderItem['quantity'] . ' is over the available limit';
            $result = Result::Error($msg, 400, false);
            return $result;
        }

        foreach ($bloodUnits as $bloodUnit) {
            $count = BulkOrderItem::where('bulk_order', $bulkOrder->id)
                ->where(['blood_unit', $bloodUnit['id']])->count();

            // Check if blood unit already exits in table
            if ($count > 0) {
                DB::rollBack();
                $result = Result::Error("A bulk order item must be assigned to a unique blood unit", 400, false);
                return $result;
            }

            // Create bulk order items
            BulkOrderItem::create([
                'bulk_order' => $bulkOrder->id,
                'blood_unit' => $bloodUnit['id'],
                'created_at' => date('Y:m:d H:i:s', time()),
                'created_by' => auth()->user()->id
            ]);
        }
    }

    // Update bulk order items
    protected function UpdateBulkOrderItems($bulkOrderItem, $bulkOrderId)
    {
        $count = BulkOrderItem::where('blood_unit', $bulkOrderItem['blood_unit'])->count();

        // Check if blood unit already exits in table
        if ($count > 0) {
            DB::rollBack();
            $result = Result::Error("A bulk order item must be assigned to a unique blood unit", 400, false);
            return $result;
        }

        // Update bulk order items
        BulkOrderItem::create([
            'bulk_order' => $bulkOrderId,
            'blood_unit' => $bulkOrderItem['blood_unit'],
            'modified_at' => date('Y:m:d H:i:s', time()),
            'modified_by' => auth()->user()->id
        ]);
    }

    // // Get count of matching blood group and product pair
    // protected function GetMatchingBloodGroupAndProduct($inventoryItem, $bloodProduct)
    // {
    //     if($inventoryItem['blood_group'])
    // } 

    // List bulk orders
    public function bulkOrders($district = "", $date = "", $time = "", $status = 0, $itemsPerPage = 5, $lastPage = 5, $firstPage = 1)
    {
        /* Params
        // district
        // date
        // time
        // status
        // itemsPerPage
        // lastPage
        // firstPage
        */

        // TODO: Implement params in get query
        return Result::ReturnList(BulkOrder::all(), 200, 'Ok', true);
    }

    // List hospital inventory
    public function HospitalInventory()
    {
        try {

            // Blood group variables
            $A = $AMinus = $B = $BMinus = $AB = $ABMinus = $O = $OMinus = 0;

            // Check if user is hospital or hospital staff
            $hospitalId = (auth()->user()->hospital != null ? auth()->user()->hospital :
                auth()->user()->hospitalStaff)['hospital_id'];

            $hospitalInventory = HospitalInventory::where('hospital_id', $hospitalId)->where('status_id', 3)->get();

            $bloodProducts = BloodProduct::all();

            $inventory = [];

            // Get blood units number under respective blood groups
            foreach ($bloodProducts as $bloodProduct) {

                // Loop through inventory to get blood unit matching blood product
                foreach ($hospitalInventory as $item) {
                    $bloodUnit = BloodUnit::where('id', $item['blood_unit'])->first();
                    if ($bloodUnit['blood_product'] == $bloodProduct['id']) {
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
                }
                $item = [
                    "blood_product" => $bloodProduct,
                    "blood_groups" => [
                        "A" => $A,
                        "A-" => $AMinus,
                        "B" => $B,
                        "B-" => $BMinus,
                        "AB" => $AB,
                        "AB-" => $ABMinus,
                        "O" => $O,
                        "O-" => $OMinus,
                    ]
                ];

                array_push($inventory, $item);
            }

            return Result::ReturnObject($inventory, 200, "OK", true);
        } catch (\Exception $exp) {
            Log::error($exp->getMessage());
            return Result::Error("Service Temporarily down", 500, false);
        }
    }

    // Create bulk order
    public function createBulkOrder(Request $request)
    {
        try {

            try {
                $request->validate([
                    'orderType' => 'required|integer',
                    // Bulk Order Items
                    'orderItems' => 'required|array',
                    'orderItems.*.blood_group' => 'required|integer',
                    'orderItems.*.blood_product' => 'required|integer',
                    'orderItems.*.quantity' => 'required|integer',
                ]);
            } catch (\Exception $exp) {
                $result = Result::Error($exp->getMessage(), 400, false);
                return $result;
            }

            // return auth()->user()->hospital;

            // Validate where user is a hospital
            if (auth()->user()->hospital == null) {
                $result = Result::Error("User does not belong to this hospital!", 400, false);
                return $result;
            }

            DB::beginTransaction();

            // Create bulk order
            $bulkOrder = BulkOrder::create([
                'hospital_id' => auth()->user()->hospital->id,
                'orderType' => $request->orderType,
                'status_id' => 3,
                'created_by' => auth()->user()->id,
                'created_at' => date('Y:m:d H:i:s', time())
            ]);

            // Loop through bulk order items
            foreach ($request->orderItems as $bulkOrderItem) {

                // Create bulk order items
                $bloodUnits = BloodUnit::where('blood_group', $bulkOrderItem['blood_group'])->where('blood_product', $bulkOrderItem['blood_product'])->where('status_id', 3)->where('date_of_expiry', '>=', date('Y-m-d', time()))
                    ->orderByDesc('date_of_expiry')->get();

                // Check if quantity of blood units dont exceed available limit
                if ($bulkOrderItem['quantity'] > $bloodUnits->count()) {
                    DB::rollBack();
                    $bloodGroup = BloodGroup::where('id', $bulkOrderItem['blood_group'])->first();
                    $bloodProduct = BloodProduct::where('id', $bulkOrderItem['blood_product'])->first();
                    $msg = 'Blood units of blood group: ' . $bloodGroup->name .
                        ',component: ' . $bloodProduct->name . ' and quantity ' .
                        $bulkOrderItem['quantity'] . ' is over the available limit';
                    $result = Result::Error($msg, 400, false);
                    return $result;
                }

                foreach ($bloodUnits as $bloodUnit) {
                    $count = BulkOrderItem::where('bulk_order', $bulkOrder['id'])->where('blood_unit', $bloodUnit->id)
                        ->get()->count();

                    // Check if blood unit already exits in table
                    if ($count > 0) {
                        DB::rollBack();
                        $result = Result::Error("A bulk order item must be assigned to a unique blood unit", 400, false);
                        return $result;
                    }

                    // Create bulk order items
                    BulkOrderItem::create([
                        'bulk_order' => $bulkOrder->id,
                        'blood_unit' => $bloodUnit['id'],
                        'created_at' => date('Y:m:d H:i:s', time()),
                        'created_by' => auth()->user()->id
                    ]);
                }
                // $this->CreateBulkOrderItems($bulkOrderItem, $bulkOrder);
            }

            DB::commit();

            return Result::ReturnMessage('Bulk order has been created', 201, true);
        } catch (\Exception $exp) {
            DB::rollBack();
            Log::error($exp->getMessage());
            return Result::Error("Service Temporarily down", 500, false);
        }
    }

    // Update bulk order
    public function UpdateBulkOrder(Request $request, $id)
    {
        try {
            try {
                $request->validate([
                    'orderType' => 'required|integer',
                    // Old Bulk Order Items
                    'orderItems' => 'required|array',
                    'orderItems.*.bulk_order' => 'required|integer',
                    'orderItems.*.blood_unit' => 'required|integer',
                    // New Bulk Order Items
                    'newOrderItems' => 'required|array',
                    'newOrderItems.*.blood_unit' => 'required|integer',
                ]);
            } catch (\Exception $exp) {
                $result = Result::Error($exp->getMessage(), 400, false);
                return $result;
            }

            DB::beginTransaction();


            // Update bulk order
            $bulkOrder = BulkOrder::where("id", $id)->update([
                // 'hospital_id' => auth()->user()->hospital->id,
                'orderType' => $request->orderType,
                // 'status_id' => 3,
                'modified_by' => auth()->user()->id,
                'modified_at' => date('Y:m:d H:i:s', time())
            ]);

            // Loop through old bulk order items
            foreach ($request->orderItems as $bulkOrderItem) {
                $this->CreateBulkOrderItems($bulkOrderItem, $bulkOrder);
            }

            DB::commit();

            return Result::ReturnMessage('Bulk order has been created', 201, true);
        } catch (\Exception $exp) {
            $result = Result::Error($exp->getMessage(), 400, false);
            return $result;
        }
    }

    // Verify bulk order

    /** TODO 
     * Approve bulk order
     * Mark individual blood unit items as taken
     * Include blood units into hospital inventory
     */


    public function ApproveBulkOrder($id)
    {
        try {

            // Check if approver is a blood bank
            if (auth()->user()['role_id'] != 1) {
                return Result::Error("Operation can only be done by a blood bank", 400, false);
            }

            $bulkOrder = BulkOrder::where('id', $id)->first();

            // Check if bulk order with this id exists
            if ($bulkOrder == null) {
                return Result::Error("Bulk order does not exist", 400, false);
            }

            DB::beginTransaction();

            // Update bulk order
            BulkOrder::where('id', $id)->update([
                'approved_by' => auth()->user()->id,
                'status_id' => 5,
                'modified_at' => date('Y:m:d H:i:s', time()),
                'modified_by' => auth()->user()->id
            ]);

            // Mark blood units attached to the blood items under this bulk order as taken
            $bulkOrderItems = BulkOrderItem::where('bulk_order', $id)->get();

            foreach ($bulkOrderItems as $bulkOrderItem) {
                BloodUnit::where('id', $bulkOrderItem['blood_unit'])->update([
                    'status_id' => 8,
                    'modified_by' => auth()->user()->id,
                    'modified_at' => date('Y:m:d H:i:s', time()),
                ]);

                // Include blood units into hospital inventory
                HospitalInventory::create([
                    'hospital_id' => $bulkOrder['hospital_id'],
                    'blood_unit' => $bulkOrderItem['blood_unit'],
                    'status_id' => 3,
                    'created_at' => date('Y:m:d H:i:s', time()),
                    'created_by' => auth()->user()->id
                ]);
            }

            DB::commit();

            return Result::ReturnMessage('Bulk order has been approved', 204, true);
        } catch (\Exception $exp) {
            DB::rollBack();
            Log::error($exp->getMessage());
            return Result::Error("Service Temporarily down", 500, false);
        }
    }

    /** PREVIOUS BULK ORDER
     * DESCRIPTION: Endpoint for getting previous bulk order details
     * ENDPOINT: /previous-order
     * METHOD: GET
     * TODO
     * - get previous bulk order which is approved
     * - get bulk order items under that bulk order
     * - determine the blood units those bulk order items fall under
     * - return a list of objects with blood products and their count
     * 
     * @return [
     *  'blood_component': [
     *  'WB' => ['UnitsPreviouslyIssued' => 0, 'UnitsUsed' => 0, 'UnitsExpired' => 0]
     * ]
     * ]
     */

    public function PreviousBulkOrder()
    {
        try {
            // Blood component variables
            $WBUnitsPreviouslyIssued = $PRBCsUnitsPreviouslyIssued = $FFPUnitsPreviouslyIssued = $FPUnitsPreviouslyIssued = $PLTUnitsPreviouslyIssued = $CRYOUnitsPreviouslyIssued = 0;
            $WBUnitsUsed = $PRBCsUnitsUsed = $FFPUnitsUsed = $FPUnitsUsed = $PLTUnitsUsed = $CRYOUnitsUsed = 0;
            $WBUnitsExpired = $PRBCsUnitsExpired = $FFPUnitsExpired = $FPUnitsExpired = $PLTUnitsExpired = $CRYOUnitsExpired = 0;



            // Get most recent approved bulk order
            $bulkOrder = BulkOrder::where('hospital_id', auth()->user()->hospital->id)->where('status_id', 5)->orderBy('created_at', 'asc')->first();

            // Get bulk order items
            if ($bulkOrder != null) {
                $bulkOrderItems = BulkOrderItem::where('bulk_order', $bulkOrder['id'])->get();
                // Get blood units for the respective bulk order items
                foreach ($bulkOrderItems as $bulkOrderItem) {
                    $bloodUnit = BloodUnit::where('id', $bulkOrderItem->blood_unit)->first();
                    // Set units previously issued
                    switch ($bloodUnit->blood_product) {
                        case 1:
                            $WBUnitsPreviouslyIssued++;
                            break;
                        case 2:
                            $PRBCsUnitsPreviouslyIssued++;
                            break;
                        case 3:
                            $FFPUnitsPreviouslyIssued++;
                            break;
                        case 4:
                            $FPUnitsPreviouslyIssued++;
                            break;
                        case 5:
                            $PLTUnitsPreviouslyIssued++;
                            break;
                        case 6:
                            $CRYOUnitsPreviouslyIssued++;
                            break;
                    }
                    // Get units which have been used
                    $inventoryItem = $bloodUnit->hospital_inventory;
                    if ($inventoryItem->status_id == 8) {
                        switch ($bloodUnit->blood_product) {
                            case 1:
                                $WBUnitsUsed++;
                                break;
                            case 2:
                                $PRBCsUnitsUsed++;
                                break;
                            case 3:
                                $FFPUnitsUsed++;
                                break;
                            case 4:
                                $FPUnitsUsed++;
                                break;
                            case 5:
                                $PLTUnitsUsed++;
                                break;
                            case 6:
                                $CRYOUnitsUsed++;
                                break;
                        }
                    }
                }
            }

            $bloodComponents = [
                ['id' => 1, 'bloodProduct' => 'WB', 'UnitsPreviouslyIssued' => $WBUnitsPreviouslyIssued, 'UnitsUsed' => $WBUnitsUsed, 'UnitsExpired' => $WBUnitsExpired, 'BalanceInStock' => ($WBUnitsPreviouslyIssued - $WBUnitsUsed), 'UnitsReturned' => 0],
                ['id' => 2, 'bloodProduct' => 'PRBCs',  'UnitsPreviouslyIssued' => $PRBCsUnitsPreviouslyIssued, 'UnitsUsed' => $PRBCsUnitsUsed, 'UnitsExpired' => $PRBCsUnitsExpired, 'BalanceInStock' => ($PRBCsUnitsPreviouslyIssued - $PRBCsUnitsUsed), 'UnitsReturned' => 0],
                ['id' => 3, 'bloodProduct' => 'FFP', 'UnitsPreviouslyIssued' => $FFPUnitsPreviouslyIssued, 'UnitsUsed' => $FFPUnitsUsed, 'UnitsExpired' => $FFPUnitsExpired, 'BalanceInStock' => ($FFPUnitsPreviouslyIssued - $FFPUnitsUsed), 'UnitsReturned' => 0],
                ['id' => 4, 'bloodProduct' => 'FP', 'UnitsPreviouslyIssued' => $FPUnitsPreviouslyIssued, 'UnitsUsed' => $FPUnitsUsed, 'UnitsExpired' => $FPUnitsExpired, 'BalanceInStock' => ($FPUnitsPreviouslyIssued - $FPUnitsUsed), 'UnitsReturned' => 0],
                ['id' => 5, 'bloodProduct' => 'PLT', 'UnitsPreviouslyIssued' => $PLTUnitsPreviouslyIssued, 'UnitsUsed' => $PLTUnitsUsed, 'UnitsExpired' => $PLTUnitsExpired, 'BalanceInStock' => ($PLTUnitsPreviouslyIssued - $PLTUnitsUsed), 'UnitsReturned' => 0],
                ['id' => 6, 'bloodProduct' => 'CRYO', 'UnitsPreviouslyIssued' => $CRYOUnitsPreviouslyIssued, 'UnitsUsed' => $CRYOUnitsUsed, 'UnitsExpired' => $CRYOUnitsExpired, 'BalanceInStock' => ($CRYOUnitsPreviouslyIssued - $CRYOUnitsUsed), 'UnitsReturned' => 0],
            ];

            return Result::ReturnObject($bloodComponents, 200, 'Ok');
        } catch (\Exception $exp) {
            return Result::InternalServerError($exp);
        }
    }
}
