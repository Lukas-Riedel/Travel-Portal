import type { AdminNavigationTarget } from "../classes/AdminNavigationTarget.ts"
import type { Airline, AirlineIdentifier, Airport, AirportIdentifier, Category, CategoryIdentifier, Highlight, Label, Photo, Place, PlaceIdentifier, Trip, TripIdentifier, Year, YearIdentifier } from "./CoreSwaggerTypes.ts"
import type { PlaceAlbum } from "./PlaceAlbum.ts"
import type { StaticNavigationTarget } from "./StaticNavigationTarget.ts"

export type Indexable = CategoryIdentifier | PlaceIdentifier | AirportIdentifier | AirlineIdentifier | Label | TripIdentifier | YearIdentifier | Photo | Highlight