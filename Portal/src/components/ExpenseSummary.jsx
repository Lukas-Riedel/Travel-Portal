import { useState, useMemo, useEffect } from "react"
import {
    Edit2, Trash2, Plus, Plane, TrainFront, Bed, Fuel, FerrisWheel, BusFront, Users, CarFront, Landmark, SquareParking,
    CarTaxiFront, DollarSign, List, PieChart, Building2
} from "lucide-react"
import { useConfiguration } from "../contexts/ConfigContext"
import { useAuth } from "../contexts/AuthContext"
import { useUserInput } from "../hooks/useUserInput.tsx"
import { useSubscriptions } from "../hooks/useSubscriptions"
import { TailSpin } from "react-loader-spinner"
import { getDateString } from "../utils/helpers"
import { useVouchers } from "../hooks/useVouchers"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { UserRole } from "../types/CoreSwaggerTypes.ts"

const expenseTypes = {
    attraction: {
        icon: FerrisWheel,
        label: "Atrakce",
        color: "text-yellow-600"
    },
    flight: {
        icon: Plane,
        label: "Letenky",
        color: "text-sky-600"
    },
    hotel: {
        icon: Bed,
        label: "Ubytování",
        color: "text-amber-800"
    },
    transport: {
        icon: TrainFront,
        label: "Doprava",
        color: "text-indigo-700"
    },
    organizedTour: {
        icon: Users,
        label: "Organizované zájezdy",
        color: "text-rose-700"
    },
    car: {
        icon: CarFront,
        label: "Provoz auta",
        color: "text-emerald-700"
    },
    visa: {
        icon: Landmark,
        label: "Víza, vstupní a výstupní poplatky",
        color: "text-green-700"
    },
    other: {
        icon: DollarSign,
        label: "Ostatní",
        color: "text-neutral-700"
    }
}

const currencies = ["AED", "AFN", "ALL", "AMD", "ANG", "AOA", "ARS", "AUD", "AWG", "AZN", "BAM", "BBD", "BDT", "BGN", "BHD", "BIF", "BMD", "BND", "BOB", "BRL", "BSD", "BTN", "BWP", "BYN", "BZD", "CAD", "CDF", "CHF", "CLP", "CNY", "COP", "CRC", "CUP", "CVE", "CZK", "DJF", "DKK", "DOP", "DZD", "EGP", "ERN", "ETB", "EUR", "FJD", "FKP", "FOK", "GBP", "GEL", "GGP", "GHS", "GIP", "GMD", "GNF", "GTQ", "GYD", "HKD", "HNL", "HRK", "HTG", "HUF", "IDR", "ILS", "IMP", "INR", "IQD", "IRR", "ISK", "JEP", "JMD", "JOD", "JPY", "KES", "KGS", "KHR", "KID", "KMF", "KRW", "KWD", "KYD", "KZT", "LAK", "LBP", "LKR", "LRD", "LSL", "LYD", "MAD", "MDL", "MGA", "MKD", "MMK", "MNT", "MOP", "MRU", "MUR", "MVR", "MWK", "MXN", "MYR", "MZN", "NAD", "NGN", "NIO", "NOK", "NPR", "NZD", "OMR", "PAB", "PEN", "PGK", "PHP", "PKR", "PLN", "PYG", "QAR", "RON", "RSD", "RUB", "RWF", "SAR", "SBD", "SCR", "SDG", "SEK", "SGD", "SHP", "SLE", "SLL", "SOS", "SRD", "SSP", "STN", "SYP", "SZL", "THB", "TJS", "TMT", "TND", "TOP", "TRY", "TTD", "TVD", "TWD", "TZS", "UAH", "UGX", "USD", "UYU", "UZS", "VES", "VND", "VUV", "WST", "XAF", "XCD", "XDR", "XOF", "XPF", "YER", "ZAR", "ZMW", "ZWL"]

const loadingRowsCount = 5

export default function ExpenseSummary({ expenses, expenseCandidates, onExpenseCreated,
    onExpenseDescriptionUpdated, onExpenseValueUpdated, onExpenseRemoved }) {
    const { configuration } = useConfiguration()
    const { hasRole } = useAuth()

    const [detailedView, setDetailedView] = useState(!!onExpenseCreated)
    const [duplicatedExpense, setDuplicatedExpense] = useState({})
    const totalCost = useMemo(() => expenses?.reduce((sum, e) => sum + (e.mainCurrencyValue || 0), 0) ?? 0, [expenses])

    const detailedRows = useMemo(() => (expenses ?? [])
        .map(expense => (
            <DetailedExpenseRow
                key={expense.id}
                expense={expense}
                onExpenseDescriptionUpdated={onExpenseDescriptionUpdated}
                onExpenseValueUpdated={onExpenseValueUpdated}
                onExpenseDuplicated={onExpenseCreated && setDuplicatedExpense}
                onExpenseRemoved={onExpenseRemoved} />
        )), [expenses, configuration, onExpenseDescriptionUpdated, onExpenseValueUpdated, onExpenseRemoved])

    const aggregatedRows = useMemo(() => Object.entries((expenses ?? [])
        .reduce((acc, e) => ((acc[e?.type] = (acc[e?.type] || 0) + (e?.mainCurrencyValue || 0)), acc), {}))
        .filter(([, cost]) => cost > 0)
        .sort((a, b) => b[1] - a[1])
        .map(([type, cost]) => (
            <AggregatedExpenseRow
                key={type}
                type={type}
                cost={cost}
                totalCost={totalCost} />
        )), [expenses, configuration, totalCost])

    const loadingRows = Array.from({ length: loadingRowsCount })
        .map((_, index) => (
            <LoadingExpenseRow
                key={index}
                detailedView={detailedView} />
        ))

    const filteredExpenseCandidates = useMemo(() => [...(expenseCandidates?.filter(candidate => !expenses?.some(expense =>
        expense.description.startsWith(candidate.description) && expense.type === candidate.type)) ?? []), duplicatedExpense],
        [expenseCandidates, expenses, duplicatedExpense])

    const expenseCandidateRows = useMemo(() => filteredExpenseCandidates.map((expenseCandidate, index) => (
        <ExpenseCandidateRow
            key={index}
            lastAddedExpense={expenses?.at(-1)}
            expenseCandidate={expenseCandidate}
            onExpenseCreated={onExpenseCreated} />
    )), [filteredExpenseCandidates, expenses, duplicatedExpense])

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
                    {hasRole(UserRole.TripExpenseEdit) ? (
                        onExpenseRemoved ? (
                            <>
                                <col className="w-[14%]" />
                                <col className="w-[46%]" />
                                <col className="w-[16%]" />
                                <col className="min-w-[16%] hidden sm:table-column" />
                                {detailedView && <col className="w-[8%]" />}
                            </>
                        ) : (
                            <>
                                <col className="w-[16%]" />
                                <col className="w-[50%]" />
                                <col className="min-w-[18%]" />
                                <col className="w-[16%] hidden sm:table-column" />
                            </>
                        )
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
                            Položka
                        </th>
                        <th
                            key="value"
                            className="w-36 p-3 text-center">
                            Cena
                        </th>
                        <th
                            key="currency"
                            className="w-36 p-3 text-center hidden sm:table-cell">
                            {detailedView ? "Přepočet" : "Podíl"}
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

function AggregatedExpenseRow({ type, cost, totalCost }) {
    const { configuration } = useConfiguration()

    const Icon = expenseTypes[type]?.icon || expenseTypes.other.icon
    return (
        <tr
            key={type}
            className="hover:bg-gray-100">
            <td className={`align-middle text-center ${expenseTypes[type]?.color || expenseTypes.other.color}`}>
                <Icon className="inline-block w-5 h-5" />
            </td>
            <td className={`p-3 text-center truncate ${expenseTypes[type]?.color || expenseTypes.other.color}`}>
                {expenseTypes[type]?.label || expenseTypes.other.label}
            </td>
            <td className={`p-3 text-center ${expenseTypes[type]?.color || expenseTypes.other.color}`}>
                <div className="flex justify-center items-center space-x-1">
                    <span>
                        {`${cost.toFixed(0)} ${configuration?.expensify?.mainCurrency ?? ""}`}
                    </span>
                </div>
            </td>
            <td className={`p-3 text-center ${expenseTypes[type]?.color || expenseTypes.other.color} hidden sm:table-cell`}>
                {totalCost > 0 ? ((100 * cost / totalCost).toFixed(1)) : "0.0"} %
            </td>
        </tr>
    )
}

function DetailedExpenseRow({ expense, onExpenseDescriptionUpdated, onExpenseValueUpdated, onExpenseRemoved, onExpenseDuplicated }) {
    const { configuration } = useConfiguration()
    const { showRemoveExpenseToast, showUpdateExpenseDescriptionToast, showUpdateExpenseValueToast } = usePredefinedUserInput()

    const handleRemove = () => {
        // TODO: Remove expense.id, provide in the caller.
        showRemoveExpenseToast(() => onExpenseRemoved(expense.id))
    }

    const handleEditDescription = () => {
        // TODO: Remove expense.id, provide in the caller.
        showUpdateExpenseDescriptionToast(expense, description => onExpenseDescriptionUpdated(expense.id, description))
    }

    const handleEditValue = () => {
        // TODO: Remove expense.id, provide in the caller.
        showUpdateExpenseValueToast(expense, currencies, (value, currency) => onExpenseValueUpdated(expense.id, value, currency))
    }

    const Icon = expenseTypes[expense.type]?.icon || expenseTypes.other.icon
    return (
        <tr
            key={expense.id}
            className="hover:bg-gray-100">
            <td className={`align-middle text-center ${expenseTypes[expense.type]?.color || expenseTypes.other.color}`}>
                {onExpenseDuplicated ? (
                    <button onClick={() => onExpenseDuplicated(expense)}>
                        <Icon className="w-5 h-5" />
                    </button>
                ) : (
                    <Icon className="inline-block w-5 h-5" />
                )}
            </td>
            <td className={`p-3 text-center ${expenseTypes[expense.type]?.color || expenseTypes.other.color}`}>
                <div className="flex justify-center items-center space-x-1">
                    <span className="truncate">
                        {expense.description}
                        {expense.subscription && ` (${expense.subscription.description} do ${getDateString(expense.subscription.expiration)})`}
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
            <td className={`p-3 text-center ${expenseTypes[expense.type]?.color || expenseTypes.other.color}`}>
                <div className="flex justify-center items-center space-x-1">
                    <span>
                        {`${expense?.value} ${expense?.currency}`}
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
            <td className={`p-3 text-center ${expenseTypes[expense.type]?.color || expenseTypes.other.color} hidden sm:table-cell`}>
                {`${expense?.mainCurrencyValue?.toFixed(0)} ${configuration?.expensify?.mainCurrency ?? ""}`}
            </td>
            {onExpenseRemoved && (
                <td className={`p-3 text-center ${expenseTypes[expense.type]?.color || expenseTypes.other.color}`}>
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

function ExpenseCandidateRow({ expenseCandidate, lastAddedExpense, onExpenseCreated }) {
    const { configuration } = useConfiguration()
    const { showFormToast } = useUserInput()
    const { showCreateExpenseToast } = usePredefinedUserInput()

    const { subscriptions } = useSubscriptions()
    const { vouchers, updateVoucherValue, removeVoucher } = useVouchers()

    const [wasEdited, setWasEdited] = useState(false)

    const [newType, setNewType] = useState(Object.keys(expenseTypes)[0])
    const [newDescription, setNewDescription] = useState("")
    const [newValue, setNewValue] = useState(0)
    const [newCurrency, setNewCurrency] = useState(undefined)

    useEffect(() => {
        if (!wasEdited) {
            setNewType(expenseCandidate?.type || Object.keys(expenseTypes)[0])
            setNewDescription(expenseCandidate?.description || "")
            setNewValue(expenseCandidate?.value || 0)
            setNewCurrency(expenseCandidate?.currency || lastAddedExpense?.currency || configuration?.expensify?.mainCurrency || "")
        }
    }, [expenseCandidate])

    const handleExpenseCreated = () => {
        showCreateExpenseToast(subscriptions, vouchers.filter(voucher => voucher.currency === newCurrency),
            (subscriptionId) => onExpenseCreated(newType, newDescription, newValue, newCurrency, subscriptionId),
            (voucherId, value) => updateVoucherValue(voucherId, value), (voucherId) => removeVoucher(voucherId))
    }

    return (
        <tr className="bg-gray-50 hover:bg-gray-100">
            <td className="text-center">
                <select
                    className="border rounded p-1 w-full"
                    value={newType}
                    onChange={e => {
                        setWasEdited(true)
                        setNewType(e.target.value)
                    }}
                    disabled={expenseCandidate?.description}>
                    {Object.keys(expenseTypes).map(typeName => (
                        <option
                            className="text-center"
                            key={typeName}
                            value={typeName}>
                            {expenseTypes[typeName].label}
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
                    placeholder="Popis"
                    disabled={expenseCandidate?.description} />
            </td>
            <td className="flex flex-col md:flex-row p-3 text-center md:space-x-0.5 space-y-0.5">
                <input
                    className="border rounded p-1 min-w-8 text-center shrink"
                    type="number"
                    min={0}
                    value={newValue}
                    onChange={e => {
                        setWasEdited(true)
                        setNewValue(e.target.value)
                    }}
                    placeholder="Cena" />
                <select
                    className="border rounded p-1 flex-1 shrink"
                    value={newCurrency}
                    onChange={e => {
                        setWasEdited(true)
                        setNewCurrency(e.target.value)
                    }}>
                    {currencies.map(currency => (
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

function LoadingExpenseRow({ detailedView }) {
    const { hasRole } = useAuth()

    return (
        <tr>
            <td className="p-3 sm:hidden" colSpan={hasRole(UserRole.TripExpenseEdit) && detailedView ? 4 : 3}>
                <div className="flex justify-center items-center w-full">
                    <TailSpin color="black" height={24} width={24} />
                </div>
            </td>
            <td className="p-3 hidden sm:table-cell" colSpan={hasRole(UserRole.TripExpenseEdit) && detailedView ? 5 : 4}>
                <div className="flex justify-center items-center w-full">
                    <TailSpin color="black" height={24} width={24} />
                </div>
            </td>
        </tr>
    )
}