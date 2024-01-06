<?php

namespace Modules\Api\Http\Controllers;

use App\Libs\ApiCaller\DivarApiCenter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder as RB;
use Modules\Api\Transformers\DivarPostResource;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function post(Request $request, $ref)
    {



        DB::beginTransaction();
        try {

            if (cache()->has("divarpost_" . $ref)) {

                $result = cache()->get("divarpost_" . $ref);
            } else {
                $divar = new DivarApiCenter();
                $result = $divar->getPost($ref);
                cache()->put("divarpost_" . $ref, $result,36500);

            }
            $out = new DivarPostResource($result);

            return RB::success($out, 200);
        } catch (\Exception $e) {
            report($e);


            DB::rollBack();
            return RB::error(422, null, ['message' => $e->getMessage()], 422);

        }
    }

}
