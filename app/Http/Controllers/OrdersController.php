<?php

namespace App\Http\Controllers;

use App\CustomHelper\Result;
use App\Models\ApprovedByOrders;
use App\Models\BloodGroup;
use App\Models\BloodProduct;
use App\Models\BloodUnit;
use App\Models\BulkOrder;
use App\Models\BulkOrderItem;
use App\Models\DispatchedByOrder;
use App\Models\Hospital;
use App\Models\HospitalInventory;
use App\Models\HospitalStaffBloodOrder;
use App\Models\OrderedByOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\ApprovedOrder;


class OrdersController extends Controller
{
    // return base 64 string
    protected function getBase64String($fileName)
    {
        $myFile = Storage::disk('public')->get('/apiFiles/' . $fileName);

        $extension = explode('.', $fileName)[1];

        // $headers = array('Content-Type: application/pdf');

        $fileName = explode('.', $fileName)[0] . time() . '.' . $extension;

        // return response()->download($myFile, $fileName, $headers);
        $prefix = null;
        if ($extension == "png") {
            $prefix = 'data:image/png;base64,';
        } else if ($extension == 'jpeg') {
            $prefix = 'data:image/jpeg;base64,';
        } else if ($extension == 'jpg') {
            $prefix = 'data:image/jpg;base64,';
        }

        return $prefix . base64_encode($myFile);
    }
    //handle files
    protected function handleFiles($base64)
    {
        //your base64 encoded data
        $file_64 = $base64;

        $extension = explode('/', explode(':', substr($file_64, 0, strpos($file_64, ';')))[1])[1];

        $replace = substr($file_64, 0, strpos($file_64, ',') + 1);

        // find substring fro replace here eg: data:image/png;base64,

        $file = str_replace($replace, '', $file_64);

        $file = str_replace(' ', '+', $file);

        $name = auth()->user()->hospital == null ? auth()->user()->email : auth()->user()->hospital->name;

        $fileName = null;
        $fileName = time() . '-' . $name . '-' . date('d-M-Y') . '.' . $extension;

        Storage::disk('public')->put('/apiFiles/' . $fileName, base64_decode($file));

        return $fileName;
    }

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

    /** LIST BULK ORDERS
     * DESCRIPTION: Handle fething of bulk orders placed by a hospital
     * ENDPOINT: /bulkOrders
     * METHOD: GET
     * TODO
     * - check if user is a hospital or blood bank
     * - fetch bulk orders made by the hospital if hospital and all if blood bank
     * 
     */
    public function bulkOrders()
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

        try {

            $pending = 0;

            $satisfied = 0;

            $bulkOrders = null;

            if (auth()->user()->role_id == 1) {
                $bulkOrders = BulkOrder::orderBy('created_at', 'desc')->get();

                foreach ($bulkOrders as $bulkOrder) {
                    $bulkOrder['DateSubmitted'] = date('d.m.Y', strtotime($bulkOrder['created_at']));
                    $bulkOrder['TimeSubmitted'] = date('H:s', strtotime($bulkOrder['created_at']));
                    $bulkOrder['Status'] = $bulkOrder->status->name;
                    $bulkOrder['Name'] = $bulkOrder->hospital->name;
                    $bulkOrder['District'] = $bulkOrder->hospital->District;

                    if ($bulkOrder->status_id == 4 || $bulkOrder->status_id == 3) {
                        $pending++;
                    } else if ($bulkOrder->status_id == 5) {
                        $satisfied++;
                    }
                }
            } else if (auth()->user()->hospital != null) {
                $bulkOrders = BulkOrder::where('hospital_id', auth()->user()->hospital->id)->get();

                foreach ($bulkOrders as $bulkOrder) {
                    $bulkOrder['DateSubmitted'] = date('d.m.Y', strtotime($bulkOrder['created_at']));
                    $bulkOrder['TimeSubmitted'] = date('H:s', strtotime($bulkOrder['created_at']));
                    $bulkOrder['Status'] = $bulkOrder->status->name;
                    $bulkOrder['OrderType'] = $bulkOrder->OrderTypes->name;

                    if ($bulkOrder->status_id == 4 || $bulkOrder->status_id == 3) {
                        $pending++;
                    } else if ($bulkOrder->status_id == 5) {
                        $satisfied++;
                    }
                }
            }

            $data = [
                'bulkOrders' => $bulkOrders,
                'pending' => $pending,
                'satisfied' => $satisfied
            ];


            return Result::ReturnObject($data, 200, 'Ok', true);
        } catch (\Exception $exp) {
            return Result::InternalServerError($exp);
        }
    }

    /** FETCH BULK ORDER DETAILS
     * DESCRIPTION: Handle fetching bulk order details
     * ENDPOINT: /bulkOrder-details
     * METHOD: GET
     * TODO
     * - check if the id passed belongs to a bulk order
     * - fetch bulk order details given the id
     * - fetch approved by and ordered by details based on the bulk order id
     * - fetch bulk order items based on bulk order id
     * 
     * @return [
     *      'bulkOrder' => $bulkOrder,
     *      'orderedBy' => $orderedBy,
     *      'approvedBy' => $approvedBy
     * ]
     */

    public function BulkOrderDetails($id)
    {
        try {
            // Check if bulk order exists
            $bulkOrder = BulkOrder::where('id', $id)->first();

            if ($bulkOrder == null) {
                return Result::Error('Bulk order does not exist', 400);
            }

            // if ($bulkOrder['hospital_id'] != auth()->user()->hospital->id) {
            //     return Result::Error('Unauthorised access of bulk order', 400);
            // }

            // get base64 string of order stamp
            $bulkOrder['Stamp'] = $this->getBase64String($bulkOrder->health_stamp);

            // Get approved by and ordered by details
            $approvedBy = ApprovedByOrders::where('bulk_order_id', $bulkOrder['id'])->first();
            // Get approved by signature in base 64 formate
            $approvedBy['Sign'] = $this->getBase64String($approvedBy->signature);

            $orderedBy = OrderedByOrder::where('bulk_order_id', $bulkOrder['id'])->first();
            // Get ordered by signature in base 64 formate
            $orderedBy['Sign'] = $this->getBase64String($orderedBy->signature);

            $orderItems = BulkOrderItem::where('bulk_order', $bulkOrder['id'])->get();

            foreach ($orderItems as $orderItem) {
                $orderItem['bloodComponent'] = $orderItem->BloodComponent->name;
                $orderItem['bloodGroup'] = $orderItem->BloodGroup->name;
            }

            $data = [
                'bulkOrder' => $bulkOrder,
                'approvedBy' => $approvedBy,
                'orderedBy' => $orderedBy,
                'orderItems' => $orderItems
            ];


            return Result::ReturnObject($data, 200, 'Ok');
        } catch (\Exception $exp) {
            return Result::InternalServerError($exp);
        }
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

    /** CREATE A BULK ORDER
     * DESCRIPTION: Handle the creation of a bulk order
     * ENDPOINT: /bulk-order
     * METHOD: POST
     * TODO
     * - create bulk order
     * - insert ordered by details into ordered by table
     * - insert approved by details into approved by table
     * 
     * Create bulk order items
     * 
     * i) iterate through order items, 
     * ii) split the keys inorder to get individual blood components and groups
     * iii) get those ids for the individual blood components and groups then store them into the order items including their quantity
     * 
     */
    public function CreateBulkOrder(Request $request)
    {
        try {

            try {
                $request->validate([
                    'orderType' => 'required|integer',
                    'orderItems' => 'required|array',
                    'healthStamp' => 'sometimes|string',
                    // Ordered by
                    'orderedBy' => 'required|array',
                    'orderedBy.name' => 'required|string',
                    'orderedBy.designation' => 'required|string',
                    'orderedBy.signature' => 'required|string',
                    // Approved by
                    'approvedBy' => 'required|array',
                    'approvedBy.name' => 'required|string',
                    'approvedBy.designation' => 'required|string',
                    'approvedBy.signature' => 'required|string'
                ]);
            } catch (\Exception $exp) {
                $result = Result::Error($exp->getMessage(), 400, false);
                return $result;
            }

            // Validate where user is a hospital
            if (auth()->user()->hospital == null) {
                return Result::Error("User does not belong to this hospital!", 400, false);
            }

            DB::beginTransaction();

            // Create bulk order
            $bulkOrder = BulkOrder::create([
                'hospital_id' => auth()->user()->hospital->id,
                'orderType' => $request->orderType,
                'health_stamp' => $this->handleFiles($request->healthStamp),
                'status_id' => 3,
                'created_by' => auth()->user()->id,
                'created_at' => date('Y:m:d H:i:s', time())
            ]);

            // Create ordered by and approved by
            OrderedByOrder::create([
                'bulk_order_id' => $bulkOrder->id,
                'name' => $request->orderedBy['name'],
                'designation' => $request->orderedBy['designation'],
                'signature' => $this->handleFiles($request->orderedBy['signature']),
            ]);

            ApprovedByOrders::create([
                'bulk_order_id' => $bulkOrder->id,
                'name' => $request->approvedBy['name'],
                'designation' => $request->approvedBy['designation'],
                'signature' => $this->handleFiles($request->approvedBy['signature']),
            ]);

            // Log::alert($request->orderItems);
            // return $request->orderItems;

            // Loop through bulk order items
            foreach ($request->orderItems as $bulkOrderItem) {
                $bloodComponent = BloodProduct::where('name', $bulkOrderItem['component'])->first()['id'];
                $bloodGroup = BloodGroup::where('name', $bulkOrderItem['group'])->first()['id'];

                if ($bulkOrderItem['quantity'] != -1) {
                    if ($bulkOrderItem['quantity'] != 0) {
                        BulkOrderItem::create([
                            'bulk_order' => $bulkOrder['id'],
                            'bloodGroup_id' => $bloodGroup,
                            'bloodComponent_id' => $bloodComponent,
                            'quantity' => $bulkOrderItem['quantity'],
                            'created_at' => date('Y:m:d H:i:s', time()),
                            'created_by' => auth()->user()->id
                        ]);
                    }
                }
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
    public function ApproveBulkOrder(Request $request, $id)
    {
        try {
            try {
                $request->validate([
                    'name' => 'required|string',
                    'designation' => 'required|string',
                    'signature' => 'required|string',
                    'time' => 'required|string'
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $result = Result::Error($e->validator->errors(), 400, false);
                return $result;
            }
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

            // Check if blood bank can satisfy this request
            $orderItems = BulkOrderItem::where('bulk_order', $id)->get();

            foreach ($orderItems as $orderItem) {
                // Get fresh blood units with matching blood component and group ids
                $bloodUnits = BloodUnit::where([
                    ['blood_product', $orderItem['bloodComponent_id']],
                    ['blood_group', $orderItem['bloodGroup_id']],
                    ['date_of_expiry', '>=', date('Y-m-d', time())],
                ])->orderBy('date_of_expiry', 'desc')->take($orderItem['quantity'])->get();

                if ($orderItem['quantity'] > count($bloodUnits)) {
                    DB::rollback();
                    return Result::Error('Blood Bank does not have enough units to fulfill this request!', 400);
                }

                // add blood units to hospital inventory
                // Mark blood units attached to the blood items under this bulk order as taken
                foreach ($bloodUnits as $bloodUnit) {
                    $bloodUnit->update([
                        'status_id' => 8,
                        'modified_by' => auth()->user()->id,
                        'modified_at' => date('Y:m:d H:i:s', time()),
                    ]);

                    // Include blood units into hospital inventory
                    HospitalInventory::create([
                        'hospital_id' => $bulkOrder['hospital_id'],
                        'blood_unit' => $bloodUnit['id'],
                        'status_id' => 3,
                        'created_at' => date('Y:m:d H:i:s', time()),
                        'created_by' => auth()->user()->id
                    ]);
                }
            }

            // Update bulk order
            BulkOrder::where('id', $id)->update([
                'approved_by' => auth()->user()->id,
                'status_id' => 5,
                'modified_at' => date('Y:m:d H:i:s', time()),
                'modified_by' => auth()->user()->id
            ]);

            // Register dispathed bulk order in dispathed order table
            DispatchedByOrder::create([
                'bulk_order_id' => $bulkOrder['id'],
                'name' => $request->name,
                'designation' => $request->designation,
                'time' => $request->time,
                'signature' => $this->handleFiles($request->signature),
                'created_at' => date('Y:m:d H:i:s', time()),
            ]);

            $email = $bulkOrder->hospital->user['email'];

            try {
                Mail::to($email)->send(new ApprovedOrder());
            } catch (\Exception $exp) {
                DB::rollBack();
                return Result::Error('Email cannot be sent', 400);
            }

            DB::commit();

            return Result::ReturnMessage('Bulk order has been approved', 201, true);
        } catch (\Exception $exp) {
            DB::rollBack();
            Log::error($exp->getMessage());
            return Result::Error("Service Temporarily down", 500, false);
        }
    }

    /** REJECT BULK ORDER
     * DESCRIPTION: Handle the rejection of a bulk order
     * ENDPOINT: /reject/bulkOrder/{id}
     * METHOD: PUT
     * TODO
     * - check if bulk order exists
     * - change status of bulk order to rejected
     * 
     */

    public function RejectBulkOrder($id)
    {
        try {
            // check if bulk order exists
            $bulkOrder = BulkOrder::where('id', $id)->first();

            if ($bulkOrder == null) {
                return Result::Error("Bulk order does not exist!", 400);
            }

            // change status of bulk order to rejected
            $bulkOrder->update([
                'status_id' => 7,
                'modified_at' => date('Y:m:d H:i:s', time()),
                'modified_by' => auth()->user()->id
            ]);

            return Result::ReturnMessage('Bulk Order has been updated', 200);
        } catch (\Exception $exp) {
            DB::rollBack();
            return Result::InternalServerError($exp);
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

    public function PreviousBulkOrder($hospitalId)
    {
        try {
            // Blood component variables
            $WBUnitsPreviouslyIssued = $PRBCsUnitsPreviouslyIssued = $FFPUnitsPreviouslyIssued = $FPUnitsPreviouslyIssued = $PLTUnitsPreviouslyIssued = $CRYOUnitsPreviouslyIssued = 0;
            $WBUnitsUsed = $PRBCsUnitsUsed = $FFPUnitsUsed = $FPUnitsUsed = $PLTUnitsUsed = $CRYOUnitsUsed = 0;
            $WBUnitsExpired = $PRBCsUnitsExpired = $FFPUnitsExpired = $FPUnitsExpired = $PLTUnitsExpired = $CRYOUnitsExpired = 0;

            // Get most recent approved bulk order

            $bulkOrder = BulkOrder::where('hospital_id', $hospitalId)->where('status_id', 5)->orderBy('created_at', 'asc')->first();

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

    /** PLACE HOSPITAL STAFF ORDER
     * DESCRIPTION: Handle the creation of a blood order made by a hospital
     * staff member
     * ENDPOINT: /hospital-staff-order
     * METHOD: POST
     * TODO
     * - create a hospital blood order
     */

    public function PlaceHospitalStaffOrder(Request $request)
    {
        try {
            try {
                $request->validate([
                    'patient_name' => 'required|string',
                    'patient_age' => 'required|integer',
                    'patient_gender' => 'required|string|in:Male,Female',
                    'patient_email' => 'sometimes|email',
                    'patient_phone' => 'required|string',
                    'blood_group_id' => 'required|integer|exists:blood_groups,id',
                    'blood_component_id' => 'required|integer|exists:blood_products,id',
                    'quantity' => 'required|integer'
                ]);
            } catch (\Exception $exp) {
                $result = Result::Error($exp->getMessage(), 400, false);
                return $result;
            }

            // Create a hospital staff order
            $request['created_at'] = date('Y:m:d H:i:s', time());
            $request['created_by'] = auth()->user()->id;
            $request['hospital_staff_id'] = auth()->user()->hospitalStaff->id;
            $request['status_id'] = 4;
            $request['hospital_id'] = auth()->user()->hospitalStaff->hospital_id;

            HospitalStaffBloodOrder::create($request->all());

            return Result::ReturnMessage('Hospital staff order has been created', 201);
        } catch (\Exception $exp) {
            return Result::InternalServerError($exp);
        }
    }

    /** FETCH HOSPITAL STAFF ORDERS
     * DESCRIPTION: Handle the fetching of hospital blood orders
     * ENDPOINT:  /hospital-staff-order
     * METHOD: GET
     * TODO
     * - check if logged in user is a hospital staff
     * - check if user is a hospital
     * - return orders
     * 
     */

    public function FetchHospitalStaffOrders()
    {
        try {

            $pending = 0;
            $processed = 0;
            $orders = [];

            $hospitalStaff = auth()->user()->hospitalStaff;

            if ($hospitalStaff == null) {
                // Check if logged in user is a hospital
                $hospital = auth()->user()->hospital;

                if ($hospital == null) {
                    return Result::Error('Wrong user!!!', 400);
                }
                $orders = HospitalStaffBloodOrder::where('hospital_id', $hospital['id'])->get();

                foreach ($orders as $order) {
                    $order['status_name'] = $order->status['name'];
                    $order['group'] = $order->bloodGroup->name;
                    $order['component'] = $order->bloodComponent->name;
                    if (($order->status_id == 3 || $order->status_id == 4)) {
                        $pending++;
                    } else if ($order->status_id == 8) {
                        $processed++;
                    }
                }
            } else {
                $orders = HospitalStaffBloodOrder::where('hospital_staff_id', $hospitalStaff['id'])->get();

                foreach ($orders as $order) {
                    $order['status_name'] = $order->status['name'];
                    $order['group'] = $order->bloodGroup->name;
                    $order['component'] = $order->bloodComponent->name;
                    if (($order->status_id == 3 || $order->status_id == 4)) {
                        $pending++;
                    } else if ($order->status_id == 5) {
                        $processed++;
                    }
                }
            }

            $result = [
                'orders' => $orders,
                'pending' => $pending,
                'processed' => $processed
            ];

            return Result::ReturnObject($result, 200, 'Ok');
        } catch (\Exception $exp) {
            return Result::InternalServerError($exp);
        }
    }

    /** APPROVE HOSPITAL STAFF ORDER
     * DESCRIPTION: Handle the approval of a hospital staff order
     * ENDPOINT: /hospital-staff-order/approve/{id}
     * METHOD: PUT
     * TODO
     * - get an unexpired blood unit from hospital inventory that matches the blood component
     * and blood group of the staff order
     * - if its present mark that blood unit as taken in its status
     * - mark that particular hospital inventory blood product as taken
     * 
     */
    public function ApproveStaffOrder($id)
    {
        try {
            $order = HospitalStaffBloodOrder::where('id', $id)->first();

            if ($order == null) {
                return Result::Error('Order does not exist!', 400);
            }

            // get units from hospital inventory
            $hospitalInventory = HospitalInventory::where('hospital_id', $order->hospital_id)->get();

            DB::beginTransaction();

            foreach ($hospitalInventory as $item) {
                // get blood units
                $bloodUnits = BloodUnit::where([
                    ['id', $item['blood_unit']],
                    ['date_of_expiry', '>=', date('Y-m-d', time())],
                    ['blood_product', $order->blood_component_id],
                    ['blood_group', $order->blood_group_id],
                ])->orderBy('date_of_expiry', 'desc')->take($order['quantity'])->get();

                if (count($bloodUnits) == $order['quantity']) {
                    // Update status of these blood units
                    // foreach ($bloodUnits as $bloodUnit) {
                    //     $bloodUnit->update([
                    //         'status_id' => 8,
                    //         'modified_by' => auth()->user()->id,
                    //         'modified_at' => date('Y:m:d H:i:s', time())
                    //     ]);
                    // }

                    // Update status of hospital inventory item
                    $item->update([
                        'status_id' => 8,
                        'modified_by' => auth()->user()->id,
                        'modified_at' => date('Y:m:d H:i:s', time())
                    ]);
                } else {
                    DB::rollBack();
                    return Result::Error('The Hospital Inventory does not have enough units to satisfy this request', 400);
                }
            }

            // Update status of hospital staff order

            $order->update([
                'status_id' => 8,
                'modified_by' => auth()->user()->id,
                'modified_at' => date('Y:m:d H:i:s', time())
            ]);

            DB::commit();

            return Result::ReturnMessage('Patient blood order has been approved', 201);
        } catch (\Exception $exp) {
            DB::rollBack();
            return Result::InternalServerError($exp);
        }
    }

    // foreach ($request->orderItems as $bulkOrderItem) {

    //     // Create bulk order items
    //     $bloodUnits = BloodUnit::where('blood_group', $bulkOrderItem['blood_group'])->where('blood_product', $bulkOrderItem['blood_product'])->where('status_id', 3)->where('date_of_expiry', '>=', date('Y-m-d', time()))
    //         ->orderByDesc('date_of_expiry')->get();

    //     // Check if quantity of blood units dont exceed available limit
    //     if ($bulkOrderItem['quantity'] > $bloodUnits->count()) {
    //         DB::rollBack();
    //         $bloodGroup = BloodGroup::where('id', $bulkOrderItem['blood_group'])->first();
    //         $bloodProduct = BloodProduct::where('id', $bulkOrderItem['blood_product'])->first();
    //         $msg = 'Blood units of blood group: ' . $bloodGroup->name .
    //             ',component: ' . $bloodProduct->name . ' and quantity ' .
    //             $bulkOrderItem['quantity'] . ' is over the available limit';
    //         $result = Result::Error($msg, 400, false);
    //         return $result;
    //     }

    // foreach ($bloodUnits as $bloodUnit) {
    //     $count = BulkOrderItem::where('bulk_order', $bulkOrder['id'])->where('blood_unit', $bloodUnit->id)
    //         ->get()->count();

    //     // Check if blood unit already exits in table
    //     if ($count > 0) {
    //         DB::rollBack();
    //         $result = Result::Error("A bulk order item must be assigned to a unique blood unit", 400, false);
    //         return $result;
    //     }

    //     // Create bulk order items
    //     BulkOrderItem::create([
    //         'bulk_order' => $bulkOrder->id,
    //         'blood_unit' => $bloodUnit['id'],
    //         'created_at' => date('Y:m:d H:i:s', time()),
    //         'created_by' => auth()->user()->id
    //     ]);
    // }
}
