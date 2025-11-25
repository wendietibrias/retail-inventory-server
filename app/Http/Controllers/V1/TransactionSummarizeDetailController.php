<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Psr\Http\Client\NetworkExceptionInterface;

class TransactionSummarizeDetailController extends Controller
{
    public function indexByTransactionSummarizeDetailId($id, Request $request)
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
