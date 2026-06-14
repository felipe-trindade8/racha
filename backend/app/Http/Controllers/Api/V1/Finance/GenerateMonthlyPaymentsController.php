<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\GenerateMonthlyPaymentsRequest;
use App\Http\Resources\FinancialTransactionResource;
use App\Services\FinancialTransactionService;
use Illuminate\Http\JsonResponse;

class GenerateMonthlyPaymentsController extends Controller
{
    public function __construct(private readonly FinancialTransactionService $financialTransactionService) {}

    public function __invoke(GenerateMonthlyPaymentsRequest $request): JsonResponse
    {
        $transactions = $this->financialTransactionService->generateMonthlyPayments(
            $request->validated('month'),
            (string) $request->validated('amount'),
        );

        return ApiResponse::success(
            FinancialTransactionResource::collection($transactions),
            status: 201,
        );
    }
}
