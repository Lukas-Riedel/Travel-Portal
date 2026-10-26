import { startOfDay, subDays } from "date-fns"
import { useMemo } from "react"

import LifestyleDashboard from "../components/LifestyleDashboard.tsx"
import LifestyleEventSummary from "../components/LifestyleEventSummary.tsx"
import LifestyleSummary from "../components/LifestyleSummary.tsx"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useConfiguration } from "../contexts/ConfigContext.tsx"
import { useLifestyleEvents } from "../hooks/useLifestyleEvents.ts"
import { UserRole } from "../types/CoreSwaggerTypes.ts"
import { getCurrentTimestamp, ONE_DAY_SECONDS } from "../utils/timeUtils.ts"

const MONITORED_PERIOD_DAYS = 30
const RECENT_PERIOD_DAYS = 10

export default function LifestylePage() {
    const { hasRole } = useAuth()
    const { configuration } = useConfiguration()
    const { events, createLifestyleEvent, removeLifestyleEvent } = useLifestyleEvents(getCurrentTimestamp() - MONITORED_PERIOD_DAYS * ONE_DAY_SECONDS)

    const recentUserEvents = useMemo(() => {
        const cutoff = Math.floor(startOfDay(subDays(new Date(), RECENT_PERIOD_DAYS - 1)).getTime() / 1000)
        return [...(events ?? [])]
            .filter(e => e.timestamp >= cutoff)
            .sort((a, b) => b.timestamp - a.timestamp)
    }, [events])

    return hasRole(UserRole.LifestyleRead) && configuration?.lifestyle?.trackingEnabled && (
        <div className="space-y-4 md:space-y-8">
            <LifestyleSummary
                events={events}
                monitoredPeriodDays={MONITORED_PERIOD_DAYS}
                onLifestyleEventCreated={hasRole(UserRole.LifestyleEdit) ? createLifestyleEvent : undefined} />
            <LifestyleDashboard
                events={events}
                monitoredPeriodDays={MONITORED_PERIOD_DAYS}
                recentPeriodDays={RECENT_PERIOD_DAYS} />
            <LifestyleEventSummary
                events={events ? recentUserEvents : null}
                onLifestyleEventDeleted={hasRole(UserRole.LifestyleEdit) ? removeLifestyleEvent : undefined} />
        </div>
    )
}
