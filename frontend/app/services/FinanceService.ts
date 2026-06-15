/**
 * Finance API communication.
 *
 * Wraps the {@link useApi} client for the financial transaction endpoints via
 * the typed CRUD helpers on {@link BaseService}. Like the other `XxxService`
 * classes it owns API communication only — list/summary state and business
 * logic live in {@link useFinances} (see docs/coding-standards.md). The status
 * transition, monthly payment generation and cash-flow summary are actions
 * rather than plain REST verbs, so they call the client directly.
 */
import { BaseService } from '~/services/BaseService'
import type { Resource } from '~/types/api'
import type {
  CashFlowQuery,
  CashFlowType,
  FinancialTransaction,
  FinancialTransactionPayload,
  FinancialTransactionQuery,
  MonthlyPaymentPayload,
  TransactionStatus,
} from '~/types/finance'

export class FinanceService extends BaseService<FinancialTransaction> {
  constructor() {
    super('financial-transactions')
  }

  /** Fetch a paginated list of transactions, optionally filtered. */
  override list(query?: FinancialTransactionQuery) {
    return super.list(query)
  }

  /** Create a transaction. */
  override create(payload: FinancialTransactionPayload) {
    return super.create(payload)
  }

  /** Update a transaction by id. */
  override update(id: number, payload: Partial<FinancialTransactionPayload>) {
    return super.update(id, payload)
  }

  /** Transition a transaction to a new status (open or paid). */
  updateStatus(id: number, status: TransactionStatus) {
    return this.client<Resource<FinancialTransaction>>(`${this.resource}/${id}/status`, {
      method: 'PATCH',
      body: { status },
    })
  }

  /** Generate the monthly payment charges for active players. */
  generateMonthly(payload: MonthlyPaymentPayload) {
    return this.client<Resource<FinancialTransaction[]>>(`${this.resource}/monthly-payments`, {
      method: 'POST',
      body: payload,
    })
  }

  /** Fetch the cash-flow summary (totals + monthly breakdown). */
  cashFlow(query?: CashFlowQuery) {
    return this.client<Resource<CashFlowType>>(`${this.resource}/cash-flow`, { query })
  }
}
