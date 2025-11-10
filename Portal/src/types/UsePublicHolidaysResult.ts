export interface UsePublicHolidaysResult {
    isPublicHoliday: (date: Date) => boolean
    isFreeDay: (date: Date) => boolean
}