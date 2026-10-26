import { useTranslation } from "react-i18next"

import { useConfiguration } from "../contexts/ConfigContext.tsx"
import { useFormatters } from "../hooks/useFormatters.ts"
import { type TimeTrackingEvent, type TimeTrackingEventType } from "../types/CoreSwaggerTypes.ts"
import { TIME_TRACKING_EVENT_TYPE_ICONS } from "../utils/eventUtils.ts"
import CardGrid from "./CardGrid.tsx"
import FactCard from "./FactCard.tsx"

interface TimeOffBalanceSummaryProps {
    timeTrackingEvents: Partial<Record<TimeTrackingEventType, TimeTrackingEvent[]>> | null
}

export default function TimeOffBalanceSummary({ timeTrackingEvents }: TimeOffBalanceSummaryProps) {
    const { t } = useTranslation()
    const { configuration } = useConfiguration()
    const { formatDuration, formatDays } = useFormatters()

    const standardWorkingHoursPerWorkingDay = 8 * (configuration?.timeTracking?.currentFte ?? 1)
    const availableEventTypes = (timeTrackingEvents ? Object.keys(timeTrackingEvents) : Object.keys(TIME_TRACKING_EVENT_TYPE_ICONS)) as TimeTrackingEventType[]

    const getBalance = (events: TimeTrackingEvent[]) => events[0]?.balance ?? 0

    return (
        <div className="space-y-4 md:space-y-8">
            <h1 className="text-2xl font-bold text-gray-800 px-4 md:px-0">
                {t("tracker.title")}
            </h1>
            <CardGrid
                rowSize={4}>
                {availableEventTypes.map(type => {
                    const Icon = TIME_TRACKING_EVENT_TYPE_ICONS[type]
                    const balance = timeTrackingEvents?.[type] != null ? getBalance(timeTrackingEvents[type]!) : undefined

                    return Icon && (
                        <FactCard
                            key={type}
                            icon={Icon}
                            label={t(`tracker.label.type.${type}`)}
                            value={balance !== undefined ? formatDays(Math.floor(balance / standardWorkingHoursPerWorkingDay + 1e-10)) : null}
                            description={balance !== undefined ? formatDuration(balance * 3600, false, true) : undefined} />
                    )
                })}
            </CardGrid>
        </div>
    )
}
