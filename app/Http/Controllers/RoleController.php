<?php

namespace App\Http\Controllers;

use App\CustomHelper\Result;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    /** FETCH ROLES
     * ENDPOINT: /roles
     * METHOD: GET
     * TODO
     * - get roles
     * 
     */

    public function FetchRoles()
    {
        try {
            return Result::ReturnList(Role::where("id", '!=', 1)->where("id", '!=', 2)->get(), 200, 'Ok', true);
        } catch (\Exception $exp) {
            Log::error($exp->getMessage());
            return Result::Error('Service Temporarily Unavailabel', 500);
        }
    }
}
