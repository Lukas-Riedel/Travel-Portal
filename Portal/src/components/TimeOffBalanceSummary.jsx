import { TailSpin } from "react-loader-spinner"
import { useMemo } from "react"
import { ClockPlus, Palmtree, Pill, Shield } from "lucide-react"
import { formatDays, formatDuration } from "../utils/formatters"
import { useConfiguration } from "../contexts/ConfigContext"

export default function TimeOffBalanceSummary({ overtimeEvents, vacationEvents, selfcareEvents, tenureEvents }) {
    const configuration = useConfiguration()

    const getBalance = events => Array.isArray(events) ? (events[0]?.balance ?? 0) : undefined
    const overtimeBalance = useMemo(() => getBalance(overtimeEvents), [overtimeEvents])
    const vacationBalance = useMemo(() => getBalance(vacationEvents), [vacationEvents])
    const selfcareBalance = useMemo(() => getBalance(selfcareEvents), [selfcareEvents])
    const tenureBalance = useMemo(() => getBalance(tenureEvents), [tenureEvents])
    const standardWorkingHoursPerWorkingDay = useMemo(() => 8 * configuration?.currentFte || 8, [configuration])

    const renderBalance = (Icon, balance) => (
        <div className="flex flex-col bg-white text-black px-6 py-3 rounded-xl text-center flex-1 shadow-md select-none border border-gray-200 min-h-[150px]">
            <div className="mb-1.5 tracking-wide flex justify-center">
                <Icon size={24} />
            </div>
            <div className="flex-grow flex items-center justify-center">
                {balance != null ? (
                    <div>
                        <div className="text-lg">
                            {formatDuration(balance * 3600)}
                        </div>
                        <div className="text-xs">
                            {formatDays((Math.round(balance / standardWorkingHoursPerWorkingDay * 2) / 2).toFixed(1))}
                        </div>
                    </div>
                ) : (
                    <div className="items-center justify-center flex h-[120px]">
                        <TailSpin color="black" height={30} width={30} />
                    </div>
                )}
            </div>
        </div>
    )


    return (
        <div className="overflow-hidden py-2 my-4 relative bg-white">
            <div className="flex flex-col sm:flex-row gap-4 px-2 items-stretch">
                {renderBalance(ClockPlus, overtimeBalance)}
                {renderBalance(Palmtree, vacationBalance)}
                {renderBalance(Pill, selfcareBalance)}
                {renderBalance(Shield, tenureBalance)}
            </div>
        </div>
    )
}