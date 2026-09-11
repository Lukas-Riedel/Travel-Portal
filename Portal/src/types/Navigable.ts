import type { AdminNavigationTarget } from "./AdminNavigationTarget.ts"
import type { Airline, AirlineIdentifier, Airport, AirportIdentifier, Category, CategoryIdentifier, Label, Place, PlaceIdentifier, Trip, TripIdentifier, Year, YearIdentifier } from "./CoreSwaggerTypes.ts"
import type { PlaceAlbum } from "./PlaceAlbum.ts"
import type { PlansNavigationTarget } from "./PlansNavigationTarget.ts"
import type { StaticNavigationTarget } from "./StaticNavigationTarget.ts"

export type Navigable = Airline | AirlineIdentifier | Airport | AirportIdentifier | Place | PlaceIdentifier | PlaceAlbum | Category | CategoryIdentifier | Label | Trip | TripIdentifier | number | Year | YearIdentifier | AdminNavigationTarget | PlansNavigationTarget | StaticNavigationTarget