import type { Place } from "../classes/Place.ts"
import type { Category } from "../types/CoreSwaggerTypes"
import PhotoTile from "./PhotoTile"

interface PlaceTileProps {
    place: Place
    mainCategory?: Category
    secondLineText?: string
}

export default function PlaceTile({ place, mainCategory, secondLineText }: PlaceTileProps) {
    return (
        <PhotoTile
            src={place.mainHighlight?.url?.thumbnail ?? place.mainHighlight?.url?.full}
            firstLineText={place.name}
            secondLineText={secondLineText}
            categories={mainCategory && [mainCategory]}
            to={place} />
    )
}