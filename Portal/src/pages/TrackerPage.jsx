import TimeOffBalanceSummary from "../components/TimeOffBalanceSummary";
import TrackerCalendar from "../components/TrackerCalendar";
import { usePublicHolidays } from "../hooks/usePublicHolidays";
import { useRegularTrips } from "../hooks/useRegularTrips";
import { useTimeTrackingEvents } from "../hooks/useTimeTrackingEvents";

export default function TrackerPage() {
    const trips = useRegularTrips({ include: "FLIGHTS" })
    const { timeTrackingEvents, createTimeTrackingEvent, removeTimeTrackingEvent } = useTimeTrackingEvents(["OVERTIME", "VACATION", "SELFCARE", "TENURE", "PLANNED_WORK"])
    const { isPublicHoliday } = usePublicHolidays()

    return (
        <>
            <TrackerCalendar
                trips={trips?.filter(trip => !trip.isDayTrips())}
                isPublicHoliday={isPublicHoliday}
                overtimeEvents={timeTrackingEvents["OVERTIME"]}
                plannedWorkEvents={timeTrackingEvents["PLANNED_WORK"]}
                vacationEvents={timeTrackingEvents["VACATION"]}
                selfcareEvents={timeTrackingEvents["SELFCARE"]}
                tenureEvents={timeTrackingEvents["TENURE"]}
                onEventCreated={createTimeTrackingEvent}
                onEventRemoved={removeTimeTrackingEvent} />
            <TimeOffBalanceSummary
                overtimeEvents={timeTrackingEvents["OVERTIME"]}
                vacationEvents={timeTrackingEvents["VACATION"]}
                selfcareEvents={timeTrackingEvents["SELFCARE"]}
                tenureEvents={timeTrackingEvents["TENURE"]} />
        </>
    )
}