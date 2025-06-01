import PhotoTile from "./PhotoTile"

export default function CategoryTile({ category }) {
    return category?.mainHighlight && (
        <PhotoTile
            src={category.mainHighlight.url.thumbnail ?? category.mainHighlight.url.full}
            firstLineText={category.name}
            categories={[category]}
            to={"/category/" + category.id} />
    )
}