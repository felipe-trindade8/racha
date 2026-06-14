<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Enums\FinancialTransactionStatusEnum;
use App\Enums\FinancialTransactionTypeEnum;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\IndexFinancialTransactionRequest;
use App\Http\Resources\FinancialTransactionResource;
use App\Models\FinancialTransaction;
use App\Services\FinancialTransactionService;
use Illuminate\Http\JsonResponse;

class IndexFinancialTransactionController extends Controller
{
    public function __construct(private readonly FinancialTransactionService $financialTransactionService) {}

    public function __invoke(IndexFinancialTransactionRequest $request): JsonResponse
    {
        $type = FinancialTransactionTypeEnum::tryFrom((string) $request->validated('type'));
        $status = FinancialTransactionStatusEnum::tryFrom((string) $request->validated('status'));

        $transactions = $this->financialTransactionService->paginate(
            $request->integer('per_page', 15),
            $type,
            $status,
            $request->validated('month'),
            $request->validated('player_id'),
        );

        $transactions->through(
            fn (FinancialTransaction $transaction): FinancialTransactionResource => new FinancialTransactionResource($transaction),
        );

        return ApiResponse::paginated($transactions);
    }
}
