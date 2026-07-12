export interface MapPoint {
    name: string
    latitude: number
    longitude: number
    color: string
    unicode?: string
    onClick?: () => Promise<void>
}