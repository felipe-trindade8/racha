<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Enums\FinancialTransactionStatusEnum;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CashFlowRequest;
use App\Services\FinancialTransactionService;
use Illuminate\Http\JsonResponse;

class CashFlowController extends Controller
{
    public function __construct(private readonly FinancialTransactionService $financialTransactionService) {}

    public function __invoke(CashFlowRequest $request): JsonResponse
    {
        $status = $request->validated('status', 'paid');

        $cashFlow = $this->financialTransactionService->cashFlow(
            $request->validated('from'),
            $request->validated('to'),
            $status === 'all' ? null : FinancialTransactionStatusEnum::from($status),
        );

        return ApiResponse::success($cashFlow);
    }
}
