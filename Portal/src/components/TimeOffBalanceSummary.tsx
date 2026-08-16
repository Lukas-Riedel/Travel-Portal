import { TailSpin } from "react-loader-spinner"
import { useMemo } from "react"
import { ClockPlus, Palmtree, Pill, Shield } from "lucide-react"
import { useConfiguration } from "../contexts/ConfigContext.tsx"
import { useFormatters } from "../hooks/useFormatters.ts"
import { TimeTrackingEventType, type TimeTrackingEvent } from "../types/CoreSwaggerTypes.ts"

const TIME_TRACKING_EVENT_TYPE_ICONS = {
    [TimeTrackingEventType.Overtime]: ClockPlus,
    [TimeTrackingEventType.Vacation]: Palmtree,
    [TimeTrackingEventType.Selfcare]: Pill,
    [TimeTrackingEventType.Tenure]: Shield
}

interface TimeOffBalanceSummaryProps {
    timeTrackingEvents: Partial<Record<TimeTrackingEventType, TimeTrackingEvent[]>> | null
}

export default function TimeOffBalanceSummary({ timeTrackingEvents }: TimeOffBalanceSummaryProps) {
    const { configuration } = useConfiguration()
    const { formatDuration, formatDays } = useFormatters()

    const standardWorkingHoursPerWorkingDay = useMemo(() => 8 * configuration?.timeTracking?.currentFte || 8, [configuration])
    const availableEventTypes = useMemo(() => timeTrackingEvents ? Object.keys(timeTrackingEvents) : Object.keys(TIME_TRACKING_EVENT_TYPE_ICONS), [timeTrackingEvents])

    const getBalance = (events: TimeTrackingEvent[]) => events[0]?.balance ?? 0

    return (
        <div className="overflow-hidden py-2 my-4 relative bg-white">
            <div className="flex flex-col sm:flex-row gap-4 px-2 items-stretch">
                {availableEventTypes.map(type => {
                    const Icon = TIME_TRACKING_EVENT_TYPE_ICONS[type as TimeTrackingEventType]
                    const balance = timeTrackingEvents && getBalance(timeTrackingEvents[type as TimeTrackingEventType])

                    return Icon && (
                        <div
                            key={type}
                            className="flex flex-col bg-white text-black px-6 py-3 rounded-xl text-center flex-1 shadow-md select-none border border-gray-200 min-h-[150px]">
                            <div className="mt-4 tracking-wide flex justify-center">
                                <Icon size={24} />
                            </div>
                            <div className="flex-grow flex items-center justify-center">
                                {balance !== null ? (
                                    <div>
                                        <div className="text-lg">
                                            {formatDuration(balance * 3600)}
                                        </div>
                                        <div className="text-xs">
                                            {formatDays(Math.floor(balance / standardWorkingHoursPerWorkingDay + 1e-10))}
                                        </div>
                                    </div>
                                ) : (
                                    <div className="items-center justify-center flex h-[120px]">
                                        <TailSpin
                                            color="black"
                                            height={30}
                                            width={30} />
                                    </div>
                                )}
                            </div>
                        </div>
                    )
                })}
            </div>
        </div>
    )
}