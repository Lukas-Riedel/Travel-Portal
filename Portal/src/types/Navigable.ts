import type { AdminNavigationTarget } from "./AdminNavigationTarget.ts"
import type { Airline, Airport, Category, Label, Place, PlaceIdentifier, Trip, TripIdentifier } from "./CoreSwaggerTypes.ts"
import type { PlaceAlbum } from "./PlaceAlbum.ts"
import type { PlansNavigationTarget } from "./PlansNavigationTarget.ts"
import type { StaticNavigationTarget } from "./StaticNavigationTarget.ts"

export type Navigable = number | Airline | Airport | Category | Place | PlaceIdentifier | Trip | TripIdentifier | Label | PlaceAlbum | AdminNavigationTarget | PlansNavigationTarget | StaticNavigationTarget