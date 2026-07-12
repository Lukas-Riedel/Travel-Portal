import type { AdminNavigationTarget } from "../classes/AdminNavigationTarget.ts"
import type { Airline, Airport, Category, Label, Place, Trip } from "./CoreSwaggerTypes.ts"
import type { PlaceAlbum } from "./PlaceAlbum.ts"

export type Navigable = Airline | Airport | Category | Place | Trip | Label | PlaceAlbum | AdminNavigationTarget