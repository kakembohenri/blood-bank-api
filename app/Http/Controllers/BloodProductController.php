<?php

namespace App\Http\Controllers;

use App\CustomHelper\Result;
use App\Models\BloodProduct;
use App\Models\BloodUnit;
use Illuminate\Http\Request;

class BloodProductController extends Controller
{
    // Fetch blood components plus number of blood units under them
    public function index()
    {
        try {
            $bloodProducts = BloodProduct::all();

            // Get blood units number under respective blood groups
            foreach ($bloodProducts as $bloodProduct) {
                $count = BloodUnit::where([
                    ['blood_product', $bloodProduct->id],
                    ['date_of_expiry', '>=', date('Y-m-d', time())],
                    ['status_id', '!=', 8]
                ])->count();

                $bloodProduct['count'] = $count;
            }

            return Result::ReturnList($bloodProducts, 200, "Ok");
        } catch (\Exception $exp) {
            $result = Result::Error($exp->getMessage(), 500);

            return $result;
        }
    }

    public function bloodUnits(Request $request)
    {
        /* Params

        //bloodComponent=
        //amount
        //bloodGroup
        //status
        //rowsPerPage
        //startPage
        //lastPage
        
        */

        dd($request);
    }

    public function getBloodUnit($id)
    {
        // Check id if exists
        $bloodUnit = BloodUnit::where('id', $id)->first();
        if ($bloodUnit == null) {
            $result = Result::Error("Record does not exist", 400);
            return $result;
        }

        return Result::ReturnObject($bloodUnit, 200, 'Ok');
    }

    // Create blood unit
    public function createBloodUnit(Request $request)
    {

        try {
            $request->validate([
                'blood_group' => 'required|integer',
                'blood_product' => 'required|integer',
                'date_of_expiry' => 'required|string',
            ], [
                'blood_group.required' => 'Blood group id is required!',
                'blood_product.required' => 'Blood product is required!',
                'date_of_expiry.required' => 'Date of expiry is required!',
            ]);

            $dateofexpiry = strtotime($request->date_of_expiry);

            if (!$dateofexpiry) {
                return  Result::Error("Provide a correct date format", 400);
            }

            BloodUnit::create([
                'blood_group' => $request->blood_group,
                'blood_product' => $request->blood_product,
                'date_of_expiry' => date('Y:m:d H:i:s', $dateofexpiry),
                'status_id' => 3,
                'created_at' => date('Y:m:d H:i:s', time()),
                'created_by' => auth()->user()->id
            ]);

            return Result::ReturnMessage('Blood Unit has been created', 201);
        } catch (\Exception $exp) {
            $result = Result::Error($exp->getMessage(), 500);

            return $result;
        }
    }

    //Update blood unit
    public function updateBloodUnit(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|integer',
                'blood_group' => 'required|integer',
                'blood_product' => 'required|integer',
                'date_of_expiry' => 'required|string',
            ], [
                'id' => 'id is required!',
                'blood_group.required' => 'Blood group id is required!',
                'blood_product.required' => 'Blood product is required!',
                'date_of_expiry.required' => 'Date of expiry is required!',
            ]);

            // Check id if exists
            if (BloodUnit::where('id', $request->id)->first() == null) {
                $result = Result::Error("Record does not exist", 400);
                return $result;
            }

            $dateofexpiry = strtotime($request->date_of_expiry);

            if (!$dateofexpiry) {
                return  Result::Error("Provide a correct date format", 400);
            }


            BloodUnit::where('id', $request->id)->update([
                'blood_group' => $request->blood_group,
                'blood_product' => $request->blood_product,
                'date_of_expiry' => date('Y:m:d H:i:s', $dateofexpiry),
                'modified_by' => auth()->user()->id,
                'modified_at' => date('Y:m:d H:i:s', time())
            ]);

            return Result::ReturnMessage('Blood Unit has been updated', 204);
        } catch (\Exception $exp) {
            $result = Result::Error($exp->getMessage(), 500);

            return $result;
        }
    }

    // Delete blood product
    public function deleteBloodUnit($id)
    {
        try {

            // Check id if exists
            if (BloodUnit::where('id', $id)->first() == null) {
                $result = Result::Error("Record does not exist", 400);
                return $result;
            }

            BloodUnit::where('id', $id)->delete();

            return Result::ReturnMessage('Blood Unit has been deleted', 204);
        } catch (\Exception $exp) {
            $result = Result::Error($exp->getMessage(), 500);

            return $result;
        }
    }
}
