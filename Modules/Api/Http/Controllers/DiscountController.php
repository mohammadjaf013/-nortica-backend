<?php

namespace Modules\Api\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder as RB;
use Modules\Api\Models\DiscountModel;
use Modules\Api\Models\KycModel;

class DiscountController extends Controller
{
    private $price = "50000";

    public function discount(Request $request)
    {
        $user = auth()->user();

        DB::beginTransaction();
        try {
            $kyc = KycModel::query()->where("user_id", $user->id)
                ->where("ref_id", $request->header('postToken'))
                ->first();

            if (!$kyc) {
                return RB::error(422, null, ['message' => 'احراز هویتی یافت نشد.'], 422);
            }

            $discount = DiscountModel::query()->where("code", $request->post("code"))->first();

            if (!$discount) {
                return RB::error(422, null, ['message' => 'کد کپن تخفیف شما اشتباه است.'], 422);
            }

            if ($discount->is_used == 1) {
                return RB::error(422, null, ['message' => 'این کپن استفاده شده است.'], 422);
            }
            if ($discount->is_used == 1) {
                return RB::error(422, null, ['message' => 'این کپن استفاده شده است.'], 422);
            }
            if (!is_null($discount->expired_at) && now()->greaterThan($discount->expired_at)) {
                return RB::error(422, null, ['message' => 'این کپن منقضی شده است.'], 422);
            }


            $discount->use_key = Str::random(50);
            $discount->update();

            DB::commit();
            return RB::success([
                'code' => $request->post("code"),
                'value' => $discount->value,
                'key' => $discount->use_key,

            ],
                200);
        } catch (\Exception $e) {
            DB::rollBack();
            return RB::error(422, null, ['message' => $e->getMessage()], 422);

        }
    }


}
