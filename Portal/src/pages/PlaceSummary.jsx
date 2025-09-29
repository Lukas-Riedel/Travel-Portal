import { Link } from "react-router-dom"
import { useMemo } from "react"
import { getDateString } from "../utils/helpers"
import { TailSpin } from "react-loader-spinner"

export default function PlaceSummary({ place }) {
    const category = useMemo(() => place && place.getCategory("mostSpecificWithMetadata"), [place])

    return (
        <div className="w-full max-w-5xl mx-auto bg-white shadow-md overflow-hidden my-10 rounded-xl">
            {place ? (
                <>
                    {place.mainHighlight?.url?.full && (
                        <Link
                            to={`/place/${place.id}`}>
                            <img
                                src={place.mainHighlight.url.full}
                                alt={place.name}
                                className="w-full aspect-[16/9] object-cover" />
                        </Link>
                    )}
                    <div className="p-4 flex flex-col items-center text-center">
                        {category && (
                            <img
                                src={`/img/flags/${category?.metadata?.unicode}.svg`}
                                alt={category?.name}
                                className="w-10 h-auto mb-3" />
                        )}
                        <Link
                            to={`/place/${place.id}`}
                            className="text-3xl mb-2 uppercase hover:text-blue-700 transition">
                            {place.name}
                        </Link>
                        <span className="text-gray-600 mb-4">
                            {getDateString(Math.max(...place.dates.map(date => date.start)))}
                        </span>
                        <p className="text-gray-600 mb-6">
                            {place.excerpt}
                        </p>
                        <Link
                            to={`/place/${place.id}`}
                            className="px-4 py-2 bg-black text-white rounded-xl shadow hover:bg-blue-700 transition">
                            Zobrazit více
                        </Link>
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
