import TimeOffBalanceSummary from "../components/TimeOffBalanceSummary";
import TrackerCalendar from "../components/TrackerCalendar";
import TripTable from "../components/TripTable";
import { useAuth } from "../contexts/AuthContext";
import { usePublicHolidays } from "../hooks/usePublicHolidays";
import { useRegularTrips } from "../hooks/useRegularTrips";
import { useTimeTrackingEvents } from "../hooks/useTimeTrackingEvents";

export default function TrackerPage() {
    const { isAdmin } = useAuth()

    const trips = useRegularTrips({ include: "FLIGHTS" })
    const { timeTrackingEvents, createTimeTrackingEvent, removeTimeTrackingEvent } = useTimeTrackingEvents(["OVERTIME", "VACATION", "SELFCARE", "TENURE", "PLANNED_WORK"])
    const { isFreeDay } = usePublicHolidays()

    return (
        <>
            <TrackerCalendar
                trips={trips?.filter(trip => !trip.isDayTrips())}
                isFreeDay={isFreeDay}
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
            {isAdmin && (
                <TripTable
                    trips={trips?.filter(trip => trip?.isFuture() && !trip?.isDayTrips())}
                    isFreeDay={isFreeDay}
                    overtimeEvents={timeTrackingEvents["OVERTIME"]}
                    plannedWorkEvents={timeTrackingEvents["PLANNED_WORK"]} />
            )}
        </>
    )
}