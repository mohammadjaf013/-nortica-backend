<?php

namespace Modules\Api\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder as RB;
use Modules\Api\Models\KycModel;
use Modules\Api\Transformers\KycResultResource;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function userData(Request $request, $id)
    {


        $kyc = KycModel::query()->where("code", $id)->where("status", "completed")->first();

        if (!$kyc) {
            return RB::error(404, null, ['message' => 'کاربری با این مشخصات یافت نشد.'], 404);
        }


        $out = new KycResultResource($kyc);
        return RB::success($out,
            200);

    }


}
