<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\UpdateFinancialTransactionRequest;
use App\Http\Resources\FinancialTransactionResource;
use App\Models\FinancialTransaction;
use App\Services\FinancialTransactionService;
use Illuminate\Http\JsonResponse;

class UpdateFinancialTransactionController extends Controller
{
    public function __construct(private readonly FinancialTransactionService $financialTransactionService) {}

    public function __invoke(UpdateFinancialTransactionRequest $request, FinancialTransaction $financialTransaction): JsonResponse
    {
        $transaction = $this->financialTransactionService->update($financialTransaction, $request->validated());

        return ApiResponse::success(new FinancialTransactionResource($transaction));
    }
}
