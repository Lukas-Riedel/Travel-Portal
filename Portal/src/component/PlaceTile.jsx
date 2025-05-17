import PhotoTile from "./PhotoTile"
import { getMostSpecificCategoryWithMetadata } from "../util/helpers"

export default function PlaceTile({ place, secondLineText }) {
    if (place.mainHighlight == null) {
        return null
    }

    return (
        <PhotoTile
            src={place.mainHighlight.url.full ?? place.mainHighlight.url.thumbnail}
            firstLineText={place.name}
            secondLineText={secondLineText}
            categories={[getMostSpecificCategoryWithMetadata(place)]}
            onClick={() => window.location.href = "place/" + place.id} />
    )
}