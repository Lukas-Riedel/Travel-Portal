import TimeOffBalanceSummary from "../components/TimeOffBalanceSummary";
import TrackerCalendar from "../components/TrackerCalendar";
import { usePublicHolidays } from "../hooks/usePublicHolidays";
import { useRegularTrips } from "../hooks/useRegularTrips";
import { useTimeTrackingEvents } from "../hooks/useTimeTrackingEvents";

export default function TrackerPage() {
    const trips = useRegularTrips({ include: "FLIGHTS" })
    const overtimeEvents = useTimeTrackingEvents({ type: "OVERTIME" })
    const vacationEvents = useTimeTrackingEvents({ type: "VACATION" })
    const selfcareEvents = useTimeTrackingEvents({ type: "SELFCARE" })
    const tenureEvents = useTimeTrackingEvents({ type: "TENURE" })
    const plannedWorkEvents = useTimeTrackingEvents({ type: "PLANNED_WORK" })
    const { isPublicHoliday } = usePublicHolidays()

    return (
        <>
            <TrackerCalendar
                trips={trips?.filter(trip => !trip.isDayTrips())}
                isPublicHoliday={isPublicHoliday}
                overtimeEvents={overtimeEvents}
                plannedWorkEvents={plannedWorkEvents}
                vacationEvents={vacationEvents}
                selfcareEvents={selfcareEvents}
                tenureEvents={tenureEvents} />
            <TimeOffBalanceSummary
                overtimeEvents={overtimeEvents}
                vacationEvents={vacationEvents}
                selfcareEvents={selfcareEvents}
                tenureEvents={tenureEvents} />
        </>
    )
}