<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Enums\FinancialTransactionStatusEnum;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\PayFinancialTransactionRequest;
use App\Http\Resources\FinancialTransactionResource;
use App\Models\FinancialTransaction;
use App\Services\FinancialTransactionService;
use Illuminate\Http\JsonResponse;

class PayFinancialTransactionController extends Controller
{
    public function __construct(private readonly FinancialTransactionService $financialTransactionService) {}

    public function __invoke(PayFinancialTransactionRequest $request, FinancialTransaction $financialTransaction): JsonResponse
    {
        $transaction = $this->financialTransactionService->updateStatus(
            $financialTransaction,
            FinancialTransactionStatusEnum::Paid,
        );

        return ApiResponse::success(new FinancialTransactionResource($transaction));
    }
}
