<?php

namespace App\Http\Controllers;

use App\CustomHelper\Result;
use App\Models\BulkOrder;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
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
            $request->validate([
                'orderType' => 'required|string',
                'orderItems' => 'required|array'
            ]);
        } catch (\Exception $exp) {
            $result = Result::Error($exp->getMessage(), 400);

            return $result;
        }
    }
}
