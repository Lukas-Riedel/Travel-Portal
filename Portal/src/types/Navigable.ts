import type { AdminNavigationTarget } from "../classes/AdminNavigationTarget.ts"
import type { Airline, Airport, Category, Label, Place, Trip } from "./CoreSwaggerTypes.ts"
import type { PlaceAlbum } from "./PlaceAlbum.ts"
import type { StaticNavigationTarget } from "./StaticNavigationTarget.ts"

export type Navigable = number | Airline | Airport | Category | Place | Trip | Label | PlaceAlbum | AdminNavigationTarget | StaticNavigationTarget