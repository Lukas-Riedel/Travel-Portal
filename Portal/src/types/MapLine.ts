import type { Coordinates } from "./Coordinates.ts"

export interface MapLine {
    from: Coordinates
    to: Coordinates
    color: string
}