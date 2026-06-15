/**
 * Finance contract types.
 *
 * These mirror the backend financial transaction endpoints (see
 * docs/architecture.md and `App\Http\Resources\FinancialTransactionResource`).
 * Resource keys are camelCase, but the write-side `player_id` and the query keys
 * (`per_page`, `player_id`, `month`) stay snake_case on purpose — they reflect
 * the JSON wire contract, not frontend code style. Money amounts arrive as
 * decimal strings (e.g. `"50.00"`), matching the backend `decimal:2` cast.
 */

/** Transaction direction, mirroring the backend `FinancialTransactionTypeEnum`. */
export const TransactionType = {
  Income: 'income',
  Expense: 'expense',
} as const

export type TransactionType = (typeof TransactionType)[keyof typeof TransactionType]

/** Transaction settlement state, mirroring `FinancialTransactionStatusEnum`. */
export const TransactionStatus = {
  Open: 'open',
  Paid: 'paid',
} as const

export type TransactionStatus = (typeof TransactionStatus)[keyof typeof TransactionStatus]

/** A financial transaction as returned by `FinancialTransactionResource`. */
export interface FinancialTransaction {
  id: number
  playerId: number | null
  description: string
  amount: string
  type: TransactionType
  date: string
  status: TransactionStatus
  createdAt: string
  updatedAt: string
}

/** Body accepted by create (`POST`) and update (`PUT`); update is partial. */
export type FinancialTransactionPayload = {
  player_id?: number | null
  description: string
  amount: number
  type: TransactionType
  date: string
}

/** Query params accepted by the transaction listing endpoint. */
export type FinancialTransactionQuery = {
  type?: TransactionType
  status?: TransactionStatus
  month?: string
  player_id?: number
  per_page?: number
  page?: number
}

/** Body for the monthly payment generation endpoint. */
export type MonthlyPaymentPayload = {
  date: string
  amount: number
}

/** Status scope accepted by the cash-flow endpoint (`all` reports every status). */
export const CashFlowStatus = {
  Open: 'open',
  Paid: 'paid',
  All: 'all',
} as const

export type CashFlowStatus = (typeof CashFlowStatus)[keyof typeof CashFlowStatus]

/** Query params accepted by the cash-flow endpoint. */
export type CashFlowQuery = {
  from?: string
  to?: string
  status?: CashFlowStatus
}

/** A single month entry in the cash-flow breakdown. */
export interface CashFlowMonth {
  month: string
  income: string
  expense: string
  balance: string
}

/** The cash-flow summary as returned by the cash-flow endpoint. */
export interface CashFlowType {
  range: {
    from: string | null
    to: string | null
  }
  status: CashFlowStatus
  totals: {
    income: string
    expense: string
    balance: string
  }
  monthly: CashFlowMonth[]
}
