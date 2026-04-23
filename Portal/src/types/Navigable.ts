import type { AdminNavigationTarget } from "../classes/AdminNavigationTarget.ts"
import type { Airline, Airport, Category, Place, Trip } from "./CoreSwaggerTypes.ts"

export type Navigable = Airline | Airport | Category | Place | Trip | AdminNavigationTarget