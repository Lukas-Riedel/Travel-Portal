import type { AdminNavigationTarget } from "../classes/AdminNavigationTarget.ts"
import type { Place } from "../classes/Place.ts"
import type { Airline, Category } from "./CoreSwaggerTypes.ts"

export type Navigable = Airline | Category | Place | AdminNavigationTarget