import type { Place as IPlace } from "../types/CoreSwaggerTypes.ts"
import { Place } from "./Place.ts"

export class DistanceAwarePlace extends Place {
    distance?: number

    public constructor(data: IPlace, distance?: number) {
        super(data)
        this.distance = distance
    }
}