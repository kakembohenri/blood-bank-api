<?php

namespace App\Http\Controllers;

use App\CustomHelper\Result;
use App\Models\BloodGroup;
use App\Models\BloodProduct;
use App\Models\BloodUnit;
use App\Models\BulkOrder;
use App\Models\BulkOrderItem;
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
            $result = Result::Error($msg, 400);
            return $result;
        }

        foreach ($bloodUnits as $bloodUnit) {
            $count = BulkOrderItem::where('bulk_order', $bulkOrder->id)
                ->where(['blood_unit', $bloodUnit['id']])->count();

            // Check if blood unit already exits in table
            if ($count > 0) {
                DB::rollBack();
                $result = Result::Error("A bulk order item must be assigned to a unique blood unit", 400);
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
            $result = Result::Error("A bulk order item must be assigned to a unique blood unit", 400);
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
        return Result::ReturnList(BulkOrder::all(), 200, 'Ok');
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
                $result = Result::Error($exp->getMessage(), 400);
                return $result;
            }

            // return auth()->user()->hospital;

            // Validate where user is a hospital
            if (auth()->user()->hospital == null) {
                $result = Result::Error("User does not belong to this hospital!", 400);
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
                    $result = Result::Error($msg, 400);
                    return $result;
                }

                foreach ($bloodUnits as $bloodUnit) {
                    $count = BulkOrderItem::where('bulk_order', $bulkOrder['id'])->where('blood_unit', $bloodUnit->id)
                        ->get()->count();

                    // Check if blood unit already exits in table
                    if ($count > 0) {
                        DB::rollBack();
                        $result = Result::Error("A bulk order item must be assigned to a unique blood unit", 400);
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

            return Result::ReturnMessage('Bulk order has been created', 201);
        } catch (\Exception $exp) {
            DB::rollBack();
            Log::error($exp->getMessage());
            return Result::Error("Service Temporarily down", 500);
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
                $result = Result::Error($exp->getMessage(), 400);
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

            return Result::ReturnMessage('Bulk order has been created', 201);
        } catch (\Exception $exp) {
            $result = Result::Error($exp->getMessage(), 400);
            return $result;
        }
    }
}
