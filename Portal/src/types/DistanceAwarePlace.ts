import type { Place } from "./CoreSwaggerTypes.ts"

export type DistanceAwarePlace = Place & { distance?: number }
