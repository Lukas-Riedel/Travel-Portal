import type { AdminNavigationTarget } from "../classes/AdminNavigationTarget.ts"
import type { Airline, Airport, Category, Label, Place, PlaceIdentifier, Trip, TripIdentifier } from "./CoreSwaggerTypes.ts"
import type { PlaceAlbum } from "./PlaceAlbum.ts"
import type { StaticNavigationTarget } from "./StaticNavigationTarget.ts"

export type Navigable = number | Airline | Airport | Category | Place | PlaceIdentifier | Trip | TripIdentifier | Label | PlaceAlbum | AdminNavigationTarget | StaticNavigationTarget