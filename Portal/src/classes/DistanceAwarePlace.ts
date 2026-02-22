import { Place } from "./Place.ts"
import type { Place as IPlace } from "../types/CoreSwaggerTypes.ts"

export class DistanceAwarePlace extends Place {
    distance?: number

    public constructor(data: IPlace, distance?: number) {
        super(data)
        this.distance = distance
    }
}