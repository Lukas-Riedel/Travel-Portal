import PhotoTile from "./PhotoTile"

export default function CategoryTile({ category }) {
    return (!category || category.mainHighlight) && (
        <PhotoTile
            src={category?.mainHighlight?.url?.thumbnail ?? category?.mainHighlight?.url?.full}
            firstLineText={category?.name}
            categories={category ? [category] : []}
            to={category ? "/category/" + category?.id : "#"} />
    )
}
