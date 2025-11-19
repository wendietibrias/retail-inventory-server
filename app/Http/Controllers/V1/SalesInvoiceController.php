<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SalesInvoiceController extends Controller
{
    public function index(Request $request){
       $request->validate([
          'is_public'=>'boolean',
          'page'=>'required|integer',
          'per_page'=>'required|integer',
          'search'=>'string',
          'sort_by'=>'string',
          'order_by'=>"string",
          'type'=>'string',
          'price_type'=>'string'
       ]);
    }

    public function generate(){}

    public function createBulk(){}

    public function create(){}

    public function changeStatus(){}

    public function detail(){}

    public function update(){}

    public function destroy(){}//should be void function
}
