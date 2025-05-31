import PhotoTile from "./PhotoTile"

export default function CategoryTile({ category }) {
    if (!category.mainHighlight) {
        return null
    }

    return (
        <PhotoTile
            src={category.mainHighlight.url.thumbnail ?? category.mainHighlight.url.full}
            firstLineText={category.name}
            categories={[category]}
            to={"/category/" + category.id} />
    )
}