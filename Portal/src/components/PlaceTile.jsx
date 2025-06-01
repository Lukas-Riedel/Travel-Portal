import PhotoTile from "./PhotoTile"

export default function PlaceTile({ place, mainCategory, secondLineText }) {
    return place?.mainHighlight && (
        <PhotoTile
            src={place.mainHighlight.url.thumbnail ?? place.mainHighlight.url.full}
            firstLineText={place.name}
            secondLineText={secondLineText}
            categories={[mainCategory]}
            to={"/place/" + place.id} />
    )
}