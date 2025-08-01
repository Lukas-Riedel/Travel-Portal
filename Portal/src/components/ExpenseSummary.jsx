import { useState, useMemo, useEffect } from "react"
import {
    Edit2, Trash2, Plus, Plane, TrainFront, Bed, Fuel, FerrisWheel, BusFront, Users, CarFront, Landmark, SquareParking,
    CarTaxiFront, DollarSign, List, PieChart, Building2
} from "lucide-react"
import { useConfiguration } from "../contexts/ConfigContext"
import { useAuth } from "../contexts/AuthContext"
import showConfirmToast from "./ConfirmToast"
import showFormToast from "./FormToast"
import { useSubscriptions } from "../hooks/useSubscriptions"
import { format, fromUnixTime } from "date-fns"
import { TailSpin } from "react-loader-spinner"
import showInputToast from "./InputToast"
import { getDateString } from "../utils/helpers"

const expenseTypes = {
    ATTRACTION: {
        icon: FerrisWheel,
        label: "Atrakce",
        color: "text-yellow-600"
    },
    FLIGHT: {
        icon: Plane,
        label: "Letenky",
        color: "text-sky-600"
    },
    HOTEL: {
        icon: Bed,
        label: "Ubytování",
        color: "text-amber-800"
    },
    INTERCITY_TRANSPORT: {
        icon: TrainFront,
        label: "Meziměstská doprava",
        color: "text-fuchsia-500"
    },
    PUBLIC_TRANSPORT: {
        icon: BusFront,
        label: "Městská doprava",
        color: "text-indigo-700"
    },
    AIRPORT_TRANSFER: {
        icon: CarTaxiFront,
        label: "Letištní transfery",
        color: "text-orange-600"
    },
    ORGANIZED_TOUR: {
        icon: Users,
        label: "Organizované zájezdy",
        color: "text-rose-700"
    },
    CAR_RENTAL: {
        icon: CarFront,
        label: "Půjčení auta",
        color: "text-emerald-700"
    },
    FUEL: {
        icon: Fuel,
        label: "Palivo",
        color: "text-red-700"
    },
    CITY_TAX: {
        icon: Building2,
        label: "Městské daně",
        color: "text-stone-600"
    },
    PARKING: {
        icon: SquareParking,
        label: "Parkování",
        color: "text-blue-900"
    },
    VISA: {
        icon: Landmark,
        label: "Víza, vstupní a výstupní poplatky",
        color: "text-green-700"
    },
    OTHER: {
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
    const { isAdmin } = useAuth()

    const [detailedView, setDetailedView] = useState(isAdmin)
    const totalCost = useMemo(() => expenses?.reduce((sum, e) => sum + (e.mainCurrencyValue || 0), 0) ?? 0, [expenses])

    const detailedRows = useMemo(() => (expenses ?? [])
        .map(expense => (
            <DetailedExpenseRow
                key={expense.id}
                expense={expense}
                onExpenseDescriptionUpdated={onExpenseDescriptionUpdated}
                onExpenseValueUpdated={onExpenseValueUpdated}
                onExpenseRemoved={onExpenseRemoved} />
        )), [expenses, configuration, isAdmin, onExpenseDescriptionUpdated, onExpenseValueUpdated, onExpenseRemoved])

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
        )), [expenses, configuration, totalCost, isAdmin])

    const loadingRows = Array.from({ length: loadingRowsCount })
        .map((_, index) => (
            <LoadingExpenseRow key={index} />
        ))

    const expenseCandidateRows = useMemo(() => [...(expenseCandidates?.filter(candidate => !expenses?.some(expense =>
        expense.description.startsWith(candidate.description) && expense.type === candidate.type)) ?? []), {}]
        .map((expenseCandidate, index) => (
            <ExpenseCandidateRow
                key={index}
                lastAddedExpense={expenses?.at(-1)}
                expenseCandidate={expenseCandidate}
                onExpenseCreated={onExpenseCreated} />
        )), [expenseCandidates, expenses, onExpenseCreated])

    const actualRows = useMemo(() => {
        if (!expenses) {
            return loadingRows
        }
        if (detailedView) {
            return [...detailedRows, ...(isAdmin && onExpenseCreated ? expenseCandidateRows : [])]
        }
        return aggregatedRows
    }, [loadingRows, detailedRows, expenseCandidateRows, detailedView, expenses])

    return (
        <div className="w-full rounded-xl my-4">
            <table className="w-full table-fixed divide-y divide-gray-200">
                <colgroup>
                    {isAdmin ? (
                        onExpenseRemoved ? (
                            <>
                                <col className="w-[14%]" />
                                <col className="w-[28%]" />
                                <col className="w-[18%]" />
                                <col className="w-[16%]" />
                                <col className="min-w-[16%] hidden sm:table-column" />
                                {detailedView && <col className="w-[8%]" />}
                            </>
                        ) : (
                            <>
                                <col className="w-[16%]" />
                                <col className="w-[30%]" />
                                <col className="w-[20%]" />
                                <col className="min-w-[18%]" />
                                <col className="w-[16%] hidden sm:table-column" />
                            </>
                        )
                    ) : (
                        <>
                            <col className="w-[11%]" />
                            <col className="w-[30%]" />
                            <col className="w-[21%]" />
                            <col className="min-w-[18%]" />
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
                            className="p-3 text-center"
                            colSpan={2}>
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
                        {isAdmin && detailedView && onExpenseRemoved && (
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
                                <td colSpan={4} />
                                <td className="p-3 text-center font-semibold hidden sm:table-cell">
                                    {`${totalCost.toFixed(0)} ${configuration?.mainCurrency ?? ""}`}
                                </td>
                                {isAdmin && <td />}
                            </>
                        ) : (
                            <>
                                <td colSpan={3} />
                                <td className="p-3 text-center font-semibold">
                                    {`${totalCost.toFixed(0)} ${configuration?.mainCurrency ?? ""}`}
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

    const Icon = expenseTypes[type]?.icon || expenseTypes.OTHER.icon
    return (
        <tr
            key={type}
            className="hover:bg-gray-100">
            <td className="p-3 text-center">
                <div className={`flex justify-center items-center ${expenseTypes[type]?.color || expenseTypes.OTHER.color}`}>
                    <Icon className="w-5 h-5 flex-shrink-0" />
                </div>
            </td>
            <td
                className={`p-3 text-center truncate ${expenseTypes[type]?.color || expenseTypes.OTHER.color}`}
                colSpan={2}>
                {expenseTypes[type]?.label || expenseTypes.OTHER.label}
            </td>
            <td className={`p-3 text-center ${expenseTypes[type]?.color || expenseTypes.OTHER.color}`}>
                <div className="flex justify-center items-center space-x-1">
                    <span>
                        {`${cost.toFixed(0)} ${configuration?.mainCurrency ?? ""}`}
                    </span>
                </div>
            </td>
            <td className={`p-3 text-center ${expenseTypes[type]?.color || expenseTypes.OTHER.color} hidden sm:table-cell`}>
                {totalCost > 0 ? ((100 * cost / totalCost).toFixed(1)) : "0.0"} %
            </td>
        </tr>
    )
}

function DetailedExpenseRow({ expense, onExpenseDescriptionUpdated, onExpenseValueUpdated, onExpenseRemoved }) {
    const { isAdmin } = useAuth()
    const { configuration } = useConfiguration()

    const handleRemove = expense => {
        showConfirmToast(
            `Opravdu chceš odstranit výdaj "${expense.description}"?`,
            "Výdaj byl úspěšně odstraněn",
            "Nepodařilo se odstranit výdaj",
            async () => onExpenseRemoved(expense.id)
        )
    }

    const handleEditDescription = expense => {
        showInputToast(
            "Zadej nový popis výdaje:",
            expense.description,
            "Popis výdaje byl úspěšně aktualizován",
            "Nepodařilo se aktualizovat popis výdaje",
            async description => onExpenseDescriptionUpdated(expense.id, description)
        )
    }

    const handleEditValue = expense => {
        showFormToast(
            "Zadej novou hodnotu a měnu výdaje:",
            [
                { label: "Hodnota", value: expense.value, required: true, type: "number", min: 0 },
                { label: "Měna", value: expense.currency, required: true, type: "select", options: currencies.map(currency => ({ id: currency, name: currency })) }
            ],
            "Hodnota výdaje byla úspěšně aktualizována",
            "Nepodařilo se aktualizovat hodnotu výdaje",
            async (value, currency) => onExpenseValueUpdated(expense.id, value, currency)
        )
    }

    const Icon = expenseTypes[expense.type]?.icon || expenseTypes.OTHER.icon
    return (
        <tr
            key={expense.id}
            className="hover:bg-gray-100">
            <td className={`p-3 text-center ${expenseTypes[expense.type]?.color || expenseTypes.OTHER.color}`}>
                <div className="flex justify-center items-center">
                    <Icon className="w-5 h-5 flex-shrink-0" />
                </div>
            </td>
            <td
                className={`p-3 text-center ${expenseTypes[expense.type]?.color || expenseTypes.OTHER.color}`}
                colSpan={2}>
                <div className="flex justify-center items-center space-x-1">
                    <span className="truncate">
                        {expense.description}
                        {expense.subscription && ` (${expense.subscription.description} do ${getDateString(expense.subscription.expiration)})`}
                    </span>
                    {isAdmin && onExpenseDescriptionUpdated && (
                        <button
                            className="p-1 btn-icon-hover"
                            onClick={() => handleEditDescription(expense)}>
                            <Edit2 size={16} />
                        </button>
                    )}
                </div>
            </td>
            <td className={`p-3 text-center ${expenseTypes[expense.type]?.color || expenseTypes.OTHER.color}`}>
                <div className="flex justify-center items-center space-x-1">
                    <span>
                        {`${expense?.value} ${expense?.currency}`}
                    </span>
                    {isAdmin && onExpenseValueUpdated && (
                        <button
                            className="p-1 btn-icon-hover"
                            onClick={() => handleEditValue(expense)}>
                            <Edit2 size={16} />
                        </button>
                    )}
                </div>
            </td>
            <td className={`p-3 text-center ${expenseTypes[expense.type]?.color || expenseTypes.OTHER.color} hidden sm:table-cell`}>
                {`${expense?.mainCurrencyValue?.toFixed(0)} ${configuration?.mainCurrency ?? ""}`}
            </td>
            {isAdmin && onExpenseRemoved && (
                <td className={`p-3 text-center ${expenseTypes[expense.type]?.color || expenseTypes.OTHER.color}`}>
                    <button
                        className="p-1 btn-icon-hover"
                        onClick={() => handleRemove(expense)}>
                        <Trash2 size={16} />
                    </button>
                </td>
            )}
        </tr>
    )
}

function ExpenseCandidateRow({ expenseCandidate, lastAddedExpense, onExpenseCreated }) {
    const { configuration } = useConfiguration()

    const subscriptions = useSubscriptions()

    const [wasEdited, setWasEdited] = useState(false)

    const [newType, setNewType] = useState(Object.keys(expenseTypes)[0])
    const [newDescription, setNewDescription] = useState("")
    const [newValue, setNewValue] = useState(0)
    const [newCurrency, setNewCurrency] = useState(undefined)
    const [newSubscriptionId, setNewSubscriptionId] = useState(undefined)

    useEffect(() => {
        if (!wasEdited) {
            setNewType(expenseCandidate?.type || Object.keys(expenseTypes)[0])
            setNewDescription(expenseCandidate?.description || "")
            setNewValue(expenseCandidate?.value || 0)
            setNewCurrency(expenseCandidate?.currency || lastAddedExpense?.currency || configuration?.mainCurrency || "")
            setNewSubscriptionId(expenseCandidate?.subscriptionId)
        }
    }, [])

    const handleExpenseCreated = () => {
        showConfirmToast("Opravdu chceš přidat nový výdaj?",
            "Nový výdaj byl úspěšně přidán",
            "Nepodařilo se přidat nový výdaj",
            async () => onExpenseCreated(newType, newDescription, newValue, newCurrency, newSubscriptionId))
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
            <td className="p-3 text-center hidden sm:table-cell">
                <select
                    className="border rounded p-1 w-full"
                    value={newSubscriptionId}
                    onChange={e => {
                        setWasEdited(true)
                        setNewSubscriptionId(e.target.value || undefined)
                    }}>
                    <option value="" />
                    {subscriptions?.map(subscription => (
                        <option
                            className="text-center"
                            key={subscription.id}
                            value={subscription.id}>
                            {`${subscription?.description} (do ${format(fromUnixTime(subscription?.expiration), "dd.MM.yyyy")})`}
                        </option>
                    ))}
                </select>
            </td>
            <td className="p-3 text-center">
                <input
                    className="border rounded p-1 w-full text-center"
                    type="number"
                    min={0}
                    value={newValue}
                    onChange={e => {
                        setWasEdited(true)
                        setNewValue(e.target.value)
                    }}
                    placeholder="Cena" />
            </td>
            <td className="p-3 text-center">
                <select
                    className="border rounded p-1 w-full"
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

function LoadingExpenseRow() {
    const { isAdmin } = useAuth()

    return (
        <tr>
            <td
                className="p-3"
                colSpan={isAdmin ? 6 : 5}>
                <div className="flex justify-center items-center h-full w-full">
                    <TailSpin
                        color="black"
                        height={24}
                        width={24} />
                </div>
            </td>
        </tr>
    )
}