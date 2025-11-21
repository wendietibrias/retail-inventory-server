<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class SalesInvoiceLogController extends Controller
{
    public function index(Request $request, $id)
    {
        try {

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500, []);
        } catch (QueryException $eq) {
            return $this->errorResponse($eq->getMessage(), 500, []);
        } catch (NetworkExceptionInterface $nei) {
            return $this->errorResponse($nei->getMessage(), 500, []);
        }
    }
}
