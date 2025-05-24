import PhotoTile from "./PhotoTile"

export default function PlaceTile({ place, secondLineText }) {
    if (place.mainHighlight == null) {
        return null
    }

    return (
        <PhotoTile
            src={place.mainHighlight.url.thumbnail ?? place.mainHighlight.url.full}
            firstLineText={place.name}
            secondLineText={secondLineText}
            categories={[place.getMostSpecificCategoryWithMetadata()]}
            to={"/place/" + place.id} />
    )
}