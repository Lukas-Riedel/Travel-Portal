import { Link } from "react-router-dom"
import { useMemo } from "react"
import { TailSpin } from "react-loader-spinner"
import { getEntityPrettyName } from "../utils/formattingUtils.ts"
import type { Place } from "../classes/Place.ts"
import { InternalCategoryCategory } from "../types/InternalCategoryCategory.ts"
import AppLink from "./AppLink.tsx"
import CategoryFlag from "./CategoryFlag.tsx"
import { useTranslation } from "react-i18next"
import { formatTimestamp } from "../utils/timeUtils.ts"

interface PlaceSummaryProps {
    place: Place | null
}

export default function PlaceSummary({ place }: PlaceSummaryProps) {
    const { t } = useTranslation()

    const category = useMemo(() => place && place.getCategory(InternalCategoryCategory.MostSpecificWithMetadata), [place])

    return (
        <div className="w-full max-w-5xl mx-auto bg-white shadow-md overflow-hidden my-10 rounded-xl">
            {place ? (
                <>
                    {place.mainHighlight?.url?.full && (
                        <AppLink
                            to={place}>
                            <img
                                src={place.mainHighlight.url.full}
                                className="w-full aspect-[16/9] object-cover brightness-100 hover:brightness-50 transition duration-700 ease-in-out" />
                        </AppLink>
                    )}
                    <div className="p-4 flex flex-col items-center text-center">
                        {category && (
                            <CategoryFlag
                                category={category}
                                className="w-10 h-auto mb-3" />
                        )}
                        <AppLink
                            to={place}
                            className="text-3xl mb-2 uppercase hover:text-blue-700 transition">
                            {getEntityPrettyName(place.name)}
                        </AppLink>
                        <span className="text-gray-600 mb-4">
                            {formatTimestamp(Math.max(...place.dates.map(date => date.start)), t("general.format.date.year.included"))}
                        </span>
                        <p className="text-gray-600 mb-6">
                            {place.excerpt}
                        </p>
                        <AppLink
                            to={place}
                            className="px-4 py-2 bg-black text-white rounded-xl shadow hover:bg-blue-700 transition">
                            {t("place.expand")}
                        </AppLink>
                    </div>
                </>
            ) : (
                <div className="flex justify-center items-center min-h-[200px]">
                    <TailSpin
                        color="black"
                        height={80}
                        width={80} />
                </div>
            )}
        </div>
    )
}
