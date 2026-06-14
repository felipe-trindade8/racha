<?php

namespace App\Policies;

use App\Models\FinancialTransaction;
use App\Models\User;

/**
 * Authorization rules for the FinancialTransaction resource.
 *
 * Finance management is administrator-only. Administrators bypass every method
 * through the global Gate::before hook in AppServiceProvider, so these methods
 * only encode the non-administrator (player) rules — and players have no finance
 * access at all, so every ability is a flat deny.
 */
class FinancialTransactionPolicy
{
    /**
     * Determine whether the user can list financial transactions.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the financial transaction.
     */
    public function view(User $user, FinancialTransaction $transaction): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create financial transactions.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the financial transaction.
     */
    public function update(User $user, FinancialTransaction $transaction): bool
    {
        return false;
    }

    /**
     * Determine whether the user can change the financial transaction's status.
     */
    public function updateStatus(User $user, FinancialTransaction $transaction): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the financial transaction.
     */
    public function delete(User $user, FinancialTransaction $transaction): bool
    {
        return false;
    }
}
