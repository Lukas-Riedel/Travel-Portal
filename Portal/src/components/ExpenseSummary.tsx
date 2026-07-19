import { useState, useMemo, useEffect } from "react"
import { Edit2, Trash2, Plus, Plane, TrainFront, Bed, FerrisWheel, Users, CarFront, Landmark, DollarSign, List, PieChart, Building2 } from "lucide-react"
import { useConfiguration } from "../contexts/ConfigContext"
import { useAuth } from "../contexts/AuthContext"
import { useSubscriptions } from "../hooks/useSubscriptions"
import { TailSpin } from "react-loader-spinner"
import { getDateString } from "../utils/helpers"
import { useVouchers } from "../hooks/useVouchers"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { ExpenseCurrency, ExpenseType, UserRole, type Expense } from "../types/CoreSwaggerTypes.ts"
import { useTranslation } from "react-i18next"
import { format, fromUnixTime } from "date-fns"
import type { ExpenseCandidate } from "../types/ExpenseCandidate.ts"

const EXPENSE_TYPES = {
    attraction: {
        icon: FerrisWheel,
        color: "text-yellow-600"
    },
    flight: {
        icon: Plane,
        color: "text-sky-600"
    },
    hotel: {
        icon: Bed,
        color: "text-amber-800"
    },
    transport: {
        icon: TrainFront,
        color: "text-indigo-700"
    },
    organizedTour: {
        icon: Users,
        color: "text-rose-700"
    },
    car: {
        icon: CarFront,
        color: "text-emerald-700"
    },
    visa: {
        icon: Landmark,
        color: "text-green-700"
    },
    other: {
        icon: DollarSign,
        color: "text-neutral-700"
    }
}

const LOADING_ROWS_COUNT = 5

interface ExpenseSummaryProps {
    expenses: Expense[] | null
    expenseCandidates?: Expense[]
    onExpenseCreated?: (type: ExpenseType, description: string, value: number, currency: ExpenseCurrency, subscriptionId?: string) => Promise<Expense>
    onExpenseDescriptionUpdated?: (expenseId: string, description: string) => Promise<Expense>
    onExpenseValueUpdated?: (expenseId: string, value: number, currency: ExpenseCurrency) => Promise<Expense>
    onExpenseRemoved?: (expenseId: string) => Promise<void>
}

export default function ExpenseSummary({ expenses, expenseCandidates, onExpenseCreated,
    onExpenseDescriptionUpdated, onExpenseValueUpdated, onExpenseRemoved }: ExpenseSummaryProps) {
    const { t } = useTranslation()
    const { configuration } = useConfiguration()

    const [detailedView, setDetailedView] = useState(!!onExpenseCreated)
    const [duplicatedExpense, setDuplicatedExpense] = useState<ExpenseCandidate>({})

    const totalCost = useMemo(() => (expenses ?? []).reduce((sum, expense) => sum + (expense.mainCurrencyValue || 0), 0), [expenses])

    const detailedRows = useMemo(() => (expenses ?? [])
        .map(expense => (
            <DetailedExpenseRow
                key={expense.id}
                expense={expense}
                onExpenseDescriptionUpdated={onExpenseDescriptionUpdated && (description => onExpenseDescriptionUpdated(expense.id, description))}
                onExpenseValueUpdated={onExpenseValueUpdated && ((value, currency) => onExpenseValueUpdated(expense.id, value, currency))}
                onExpenseDuplicated={onExpenseCreated && (() => setDuplicatedExpense(expense))}
                onExpenseRemoved={onExpenseRemoved && (() => onExpenseRemoved(expense.id))} />
        )), [expenses, onExpenseDescriptionUpdated, onExpenseValueUpdated, onExpenseRemoved, onExpenseCreated, setDuplicatedExpense])

    const aggregatedRows = useMemo(() => Object.entries((expenses ?? [])
        .reduce((acc: Record<string, number>, expense) => ((acc[expense.type] = (acc[expense.type] || 0) + (expense.mainCurrencyValue || 0)), acc), {}))
        .filter(([, cost]) => cost > 0)
        .sort((a, b) => b[1] - a[1])
        .map(([type, cost]) => (
            <AggregatedExpenseRow
                key={type}
                type={type as ExpenseType}
                cost={cost}
                totalCost={totalCost} />
        )), [expenses, totalCost])

    const loadingRows = useMemo(() => Array.from({ length: LOADING_ROWS_COUNT })
        .map((_, index) => (
            <LoadingExpenseRow
                key={index}
                detailedView={detailedView}
                hasRemoveButton={!!onExpenseRemoved} />
        )), [detailedView])

    const filteredExpenseCandidates = useMemo(() => [...(expenseCandidates?.filter(candidate => !expenses?.some(expense =>
        expense.description.startsWith(candidate.description) && expense.type === candidate.type)) ?? []), duplicatedExpense],
        [expenseCandidates, expenses, duplicatedExpense])

    const expenseCandidateRows = useMemo(() => filteredExpenseCandidates.map((expenseCandidate, index) => (
        <ExpenseCandidateRow
            key={index}
            lastAddedExpense={expenses?.at(-1)}
            expenseCandidate={expenseCandidate}
            onExpenseCreated={onExpenseCreated} />
    )), [filteredExpenseCandidates, expenses, duplicatedExpense, onExpenseCreated])

    const actualRows = useMemo(() => {
        if (!expenses) {
            return loadingRows
        }
        if (detailedView) {
            return [...detailedRows, ...(onExpenseCreated ? expenseCandidateRows : [])]
        }
        return aggregatedRows
    }, [loadingRows, detailedRows, expenseCandidateRows, detailedView, expenses, onExpenseCreated])

    return (
        <div className="w-full rounded-xl my-4">
            <table className="w-full table-fixed divide-y divide-gray-200">
                <colgroup>
                    {onExpenseRemoved ? (
                        <>
                            <col className="w-[14%]" />
                            <col className="w-[46%]" />
                            <col className="w-[16%]" />
                            <col className="min-w-[16%] hidden sm:table-column" />
                            {detailedView && <col className="w-[8%]" />}
                        </>
                    ) : (
                        <>
                            <col className="w-[11%]" />
                            <col className="min-w-[40%]" />
                            <col className="w-[30%]" />
                            <col className="w-[18%] hidden sm:table-column" />
                        </>
                    )}
                </colgroup>
                <thead className="bg-gray-100">
                    <tr>
                        <th
                            key="type"
                            className="w-12 text-center">
                            <div className="flex justify-center items-center">
                                <button
                                    onClick={() => setDetailedView(prev => !prev)}
                                    className="btn-chip-gray">
                                    {detailedView ? <PieChart size={16} /> : <List size={16} />}
                                </button>
                            </div>
                        </th>
                        <th
                            key="description"
                            className="p-3 text-center">
                            {t("expense.label.item")}
                        </th>
                        <th
                            key="value"
                            className="w-36 p-3 text-center">
                            {t("expense.label.price")}
                        </th>
                        <th
                            key="currency"
                            className="w-36 p-3 text-center hidden sm:table-cell">
                            {detailedView ? t("expense.label.sum") : t("expense.label.share")}
                        </th>
                        {detailedView && onExpenseRemoved && (
                            <th
                                key="management"
                                className="w-12 text-center" />
                        )}
                    </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                    {actualRows}
                    <tr>
                        {detailedView ? (
                            <>
                                <td colSpan={3} />
                                <td className="p-3 text-center font-semibold hidden sm:table-cell">
                                    {`${totalCost.toFixed(0)} ${configuration?.expensify?.mainCurrency ?? ""}`}
                                </td>
                                {onExpenseRemoved && <td />}
                            </>
                        ) : (
                            <>
                                <td colSpan={2} />
                                <td className="p-3 text-center font-semibold">
                                    {`${totalCost.toFixed(0)} ${configuration?.expensify?.mainCurrency ?? ""}`}
                                </td>
                                <td className="hidden sm:table-cell" />
                            </>
                        )}
                    </tr>
                </tbody>
            </table>
        </div>
    )
}

interface AggregatedExpenseRowProps {
    type: ExpenseType
    cost: number
    totalCost: number
}

function AggregatedExpenseRow({ type, cost, totalCost }: AggregatedExpenseRowProps) {
    const { t } = useTranslation()
    const { configuration } = useConfiguration()

    const Icon = EXPENSE_TYPES[type]?.icon || EXPENSE_TYPES.other.icon
    return (
        <tr
            key={type}
            className="hover:bg-gray-100">
            <td className={`align-middle text-center ${EXPENSE_TYPES[type]?.color || EXPENSE_TYPES.other.color}`}>
                <Icon className="inline-block w-5 h-5" />
            </td>
            <td className={`p-3 text-center truncate ${EXPENSE_TYPES[type]?.color || EXPENSE_TYPES.other.color}`}>
                {t(`expense.name.${type}`)}
            </td>
            <td className={`p-3 text-center ${EXPENSE_TYPES[type]?.color || EXPENSE_TYPES.other.color}`}>
                <div className="flex justify-center items-center space-x-1">
                    <span>
                        {`${cost.toFixed(0)} ${configuration?.expensify?.mainCurrency ?? ""}`}
                    </span>
                </div>
            </td>
            <td className={`p-3 text-center ${EXPENSE_TYPES[type]?.color || EXPENSE_TYPES.other.color} hidden sm:table-cell`}>
                {totalCost > 0 ? ((100 * cost / totalCost).toFixed(1)) : "0.0"} %
            </td>
        </tr>
    )
}

interface DetailedExpenseRowProps {
    expense: Expense
    onExpenseDescriptionUpdated?: (description: string) => Promise<Expense>
    onExpenseValueUpdated?: (value: number, currency: ExpenseCurrency) => Promise<Expense>
    onExpenseRemoved?: () => Promise<void>
    onExpenseDuplicated?: () => void
}

function DetailedExpenseRow({ expense, onExpenseDescriptionUpdated, onExpenseValueUpdated, onExpenseRemoved, onExpenseDuplicated }: DetailedExpenseRowProps) {
    const { t } = useTranslation()
    const { configuration } = useConfiguration()
    const { showRemoveExpenseToast, showUpdateExpenseDescriptionToast, showUpdateExpenseValueToast } = usePredefinedUserInput()

    const handleRemove = () => {
        if (onExpenseRemoved) {
            showRemoveExpenseToast(onExpenseRemoved)
        }
    }

    const handleEditDescription = () => {
        if (onExpenseDescriptionUpdated) {
            showUpdateExpenseDescriptionToast(expense, onExpenseDescriptionUpdated)
        }
    }

    const handleEditValue = () => {
        if (onExpenseValueUpdated) {
            showUpdateExpenseValueToast(expense, onExpenseValueUpdated)
        }
    }

    const Icon = EXPENSE_TYPES[expense.type]?.icon || EXPENSE_TYPES.other.icon
    return (
        <tr
            key={expense.id}
            className="hover:bg-gray-100">
            <td className={`align-middle text-center ${EXPENSE_TYPES[expense.type]?.color || EXPENSE_TYPES.other.color}`}>
                {onExpenseDuplicated ? (
                    <button onClick={onExpenseDuplicated}>
                        <Icon className="w-5 h-5" />
                    </button>
                ) : (
                    <Icon className="inline-block w-5 h-5" />
                )}
            </td>
            <td className={`p-3 text-center ${EXPENSE_TYPES[expense.type]?.color || EXPENSE_TYPES.other.color}`}>
                <div className="flex justify-center items-center space-x-1">
                    <span className="truncate">
                        {expense.description}
                        {expense.subscription && ` (${t("subscription.format", { description: expense.subscription.description, expiration: format(fromUnixTime(expense.subscription.expiration), t("general.format.date.year.included")) })})`}
                    </span>
                    {onExpenseDescriptionUpdated && (
                        <button
                            className="p-1 btn-icon-hover"
                            onClick={handleEditDescription}>
                            <Edit2 size={16} />
                        </button>
                    )}
                </div>
            </td>
            <td className={`p-3 text-center ${EXPENSE_TYPES[expense.type]?.color || EXPENSE_TYPES.other.color}`}>
                <div className="flex justify-center items-center space-x-1">
                    <span>
                        {`${expense.value} ${expense.currency}`}
                    </span>
                    {onExpenseValueUpdated && (
                        <button
                            className="p-1 btn-icon-hover"
                            onClick={handleEditValue}>
                            <Edit2 size={16} />
                        </button>
                    )}
                </div>
            </td>
            <td className={`p-3 text-center ${EXPENSE_TYPES[expense.type]?.color || EXPENSE_TYPES.other.color} hidden sm:table-cell`}>
                {`${expense.mainCurrencyValue.toFixed(0)} ${configuration?.expensify?.mainCurrency ?? ""}`}
            </td>
            {onExpenseRemoved && (
                <td className={`p-3 text-center ${EXPENSE_TYPES[expense.type]?.color || EXPENSE_TYPES.other.color}`}>
                    <button
                        className="p-1 btn-icon-hover"
                        onClick={handleRemove}>
                        <Trash2 size={16} />
                    </button>
                </td>
            )}
        </tr>
    )
}

interface ExpenseCandidateRowProps {
    expenseCandidate: ExpenseCandidate
    lastAddedExpense?: Expense
    onExpenseCreated?: (type: ExpenseType, description: string, value: number, currency: ExpenseCurrency, subscriptionId?: string) => Promise<Expense>
}

function ExpenseCandidateRow({ expenseCandidate, lastAddedExpense, onExpenseCreated }: ExpenseCandidateRowProps) {
    const { t, i18n } = useTranslation()
    const { configuration } = useConfiguration()
    const { showCreateExpenseToast } = usePredefinedUserInput()

    const { subscriptions } = useSubscriptions()
    const { vouchers, updateVoucherValue, removeVoucher } = useVouchers()

    const getSortedExpenseTypes = () => Object.values(ExpenseType).sort((a, b) => t(`expense.name.${a}`).localeCompare(t(`expense.name.${b}`), i18n.language))

    const [wasEdited, setWasEdited] = useState(false)
    const [newType, setNewType] = useState(getSortedExpenseTypes()[0])
    const [newDescription, setNewDescription] = useState("")
    const [newValue, setNewValue] = useState<number | null>(0)
    const [newCurrency, setNewCurrency] = useState(Object.values(ExpenseCurrency)[0])

    useEffect(() => {
        if (!wasEdited) {
            setNewType(expenseCandidate?.type || getSortedExpenseTypes()[0])
            setNewDescription(expenseCandidate?.description || "")
            setNewValue(expenseCandidate?.value !== undefined ? expenseCandidate.value : null)
            setNewCurrency(expenseCandidate?.currency || lastAddedExpense?.currency || configuration?.expensify?.mainCurrency || Object.values(ExpenseCurrency)[0])
        }
    }, [expenseCandidate])

    const handleExpenseCreated = () => {
        if (onExpenseCreated) {
            showCreateExpenseToast(subscriptions ?? [], (vouchers ?? []).filter(voucher => voucher.currency === newCurrency),
                (subscriptionId) => onExpenseCreated(newType, newDescription, newValue, newCurrency, subscriptionId),
                (voucherId, value) => updateVoucherValue(voucherId, value), (voucherId) => removeVoucher(voucherId))
        }
    }

    return (
        <tr className="bg-gray-50 hover:bg-gray-100">
            <td className="text-center">
                <select
                    className="border rounded p-1 w-full"
                    value={newType}
                    onChange={e => {
                        setWasEdited(true)
                        setNewType(e.target.value as ExpenseType)
                    }}
                    disabled={!!expenseCandidate?.description}>
                    {getSortedExpenseTypes().map(expenseType => (
                        <option
                            className="text-center"
                            key={expenseType}
                            value={expenseType}>
                            {t(`expense.name.${expenseType}`)}
                        </option>
                    ))}
                </select>
            </td>
            <td className="p-3 text-center">
                <input
                    className="border rounded p-1 w-full text-center"
                    type="text"
                    value={newDescription}
                    onChange={e => {
                        setWasEdited(true)
                        setNewDescription(e.target.value)
                    }}
                    placeholder={t("expense.label.description")}
                    disabled={!!expenseCandidate?.description} />
            </td>
            <td className="flex flex-col md:flex-row p-3 text-center md:space-x-0.5 space-y-0.5">
                <input
                    className="border rounded p-1 min-w-8 text-center shrink"
                    type="number"
                    min={0}
                    step="any"
                    value={newValue === null ? "" : newValue}
                    onChange={e => {
                        setWasEdited(true)

                        const value = e.target.value
                        if (value === "") {
                            setNewValue(null)
                        }
                        else {
                            const parsed = Number(value)
                            if (!isNaN(parsed)) {
                                setNewValue(parsed)
                            }
                        }
                    }}
                    placeholder={t("expense.label.price")} />
                <select
                    className="border rounded p-1 flex-1 shrink"
                    value={newCurrency}
                    onChange={e => {
                        setWasEdited(true)
                        setNewCurrency(e.target.value as ExpenseCurrency)
                    }}>
                    {Object.values(ExpenseCurrency).map(currency => (
                        <option
                            className="text-center"
                            key={currency}
                            value={currency}>
                            {currency}
                        </option>
                    ))}
                </select>
            </td>
            <td className="hidden sm:table-cell" />
            <td className="text-center">
                <button
                    className="p-1 hover:bg-gray-200 rounded"
                    onClick={handleExpenseCreated}>
                    <Plus size={16} />
                </button>
            </td>
        </tr>
    )
}

interface LoadingExpenseRowProps {
    detailedView: boolean
    hasRemoveButton: boolean
}

function LoadingExpenseRow({ detailedView, hasRemoveButton }: LoadingExpenseRowProps) {
    return (
        <tr>
            <td
                className="p-3 sm:hidden"
                colSpan={hasRemoveButton && detailedView ? 4 : 3}>
                <div className="flex justify-center items-center w-full">
                    <TailSpin color="black" height={24} width={24} />
                </div>
            </td>
            <td
                className="p-3 hidden sm:table-cell"
                colSpan={hasRemoveButton && detailedView ? 5 : 4}>
                <div className="flex justify-center items-center w-full">
                    <TailSpin color="black" height={24} width={24} />
                </div>
            </td>
        </tr>
    )
}