<?php

namespace Modules\Api\Http\Requests;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder as RB;
class DataSetRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        $obj = RB::error(422, null, ['errors' => $validator->errors()->toArray()], 422);//;
        throw new HttpResponseException($obj);;
    }

    function validationData()
    {
        return $this->post();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        return [
            'ssn' => 'required|min:10|max:10',
            'birthday' => 'required',
        ];
    }


}
