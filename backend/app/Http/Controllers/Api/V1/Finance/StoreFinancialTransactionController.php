<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreFinancialTransactionRequest;
use App\Http\Resources\FinancialTransactionResource;
use App\Services\FinancialTransactionService;
use Illuminate\Http\JsonResponse;

class StoreFinancialTransactionController extends Controller
{
    public function __construct(private readonly FinancialTransactionService $financialTransactionService) {}

    public function __invoke(StoreFinancialTransactionRequest $request): JsonResponse
    {
        $transaction = $this->financialTransactionService->create($request->validated());

        return ApiResponse::success(new FinancialTransactionResource($transaction), status: 201);
    }
}
