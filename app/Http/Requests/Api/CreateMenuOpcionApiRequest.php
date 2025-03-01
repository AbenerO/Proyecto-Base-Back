<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\MenuOpcion;



class CreateMenuOpcionApiRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {

           return MenuOpcion::$rules;

    }
}

