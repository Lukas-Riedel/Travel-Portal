import { createPlaceLabel, removePlaceLabel } from "../clients/coreClient";
import AppLink from "../components/AppLink";
import Card from "../components/Card";
import CardGrid from "../components/CardGrid";
import CategoryFlag from "../components/CategoryFlag";
import LabelBar from "../components/LabelBar";
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces";
import { CategoryCategory, PlaceIncludedEntity } from "../types/CoreSwaggerTypes";
import { InternalCategoryCategory } from "../types/InternalCategoryCategory";
import { getEntityPrettyName } from "../utils/formattingUtils";
import { getPlaceCategory } from "../utils/placeUtils";
import { getCurrentTimestamp } from "../utils/timeUtils";

export default function LabelsPage() {
    const { places } = useTimeFilteredRegularPlaces({ maxEnd: getCurrentTimestamp(), include: [PlaceIncludedEntity.Labels, PlaceIncludedEntity.Categories] })

    return places && (
        <CardGrid
            columnSize={10}
            rowSize={1}>
            {places.map(place => (
                <Card>
                    <div className="flex justify-start items-center">
                        <CategoryFlag
                            category={getPlaceCategory(place, InternalCategoryCategory.MostSpecificWithMetadata)}
                            className="w-7 h-auto flex-shrink-0" />
                        <AppLink
                            to={place}
                            className="ml-2 hover:underline text-lg font-semibold truncate">
                            {`${getEntityPrettyName(place.name)} (${getPlaceCategory(place, CategoryCategory.Administrative)?.name})`}
                        </AppLink>
                    </div>
                    <LabelBar
                        labels={place ? (place.labels ?? []) : null}
                        onLabelAdded={label => createPlaceLabel(place.id, label)}
                        onLabelRemoved={labelId => removePlaceLabel(place.id, labelId)} />
                </Card>
            ))}
        </CardGrid>
    )
}