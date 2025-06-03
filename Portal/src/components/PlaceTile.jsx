import PhotoTile from "./PhotoTile"

export default function PlaceTile({ place, mainCategory, secondLineText }) {
    return (
        <PhotoTile
            src={place?.mainHighlight?.url?.thumbnail ?? place?.mainHighlight?.url?.full}
            firstLineText={place?.name}
            secondLineText={secondLineText}
            categories={mainCategory && [mainCategory]}
            to={"/place/" + place?.id} />
    )
}