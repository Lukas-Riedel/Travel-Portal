import type { Coordinates } from "./Coordinates.ts"

export type UseCachedCoordinatesResult = (address: string) => Promise<Coordinates | null>
