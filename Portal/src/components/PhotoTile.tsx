import { Link, type To } from "react-router-dom"
import LoadingTile from "./LoadingTile.tsx"
import { cn, getEntityPrettyName } from "../utils/formattingUtils.ts"
import type { Category } from "../types/CoreSwaggerTypes.ts"
import type { Navigable } from "../types/Navigable.ts"
import AppLink from "./AppLink.tsx"

interface PhotoTileProps {
    src: string
    firstLineText?: string
    secondLineText?: string
    categories?: Category[]
    to?: string | Navigable
    onClick?: () => void
    className?: string
}

export default function PhotoTile({ src, firstLineText, secondLineText, categories, to, onClick, className = "w-[350px] h-[233px]" }: PhotoTileProps) {
    if (!src) {
        return (
            <LoadingTile className={className} />
        )
    }

    return (
        <div className={cn("relative mx-auto", className)}>
            <InteractiveWrapper
                to={to}
                onClick={onClick}
                className="block cursor-pointer">
                <img
                    src={src}
                    alt={firstLineText ?? ""}
                    className="w-full h-full object-cover brightness-100 hover:brightness-50 transition duration-700 ease-in-out rounded-xl"
                />
                <div className={`absolute left-0 bottom-0 w-full flex items-center justify-center text-white text-sm uppercase font-medium leading-[170%] py-4 rounded-b-xl
                        ${categories || firstLineText || secondLineText ? "bg-gradient-to-t from-black via-black/70 to-transparent" : ""}`}>
                    <ul className="list-none m-0 p-0 flex flex-col items-center gap-0.5 text-base">
                        <li className="flex flex-wrap justify-center gap-1 mx-2">
                            {categories?.map(category => (
                                category?.metadata?.unicode && (
                                    <img
                                        key={category.id}
                                        src={`/img/flags/${category.metadata.unicode}.svg`}
                                        className="w-4 h-4 align-middle object-cover rounded-xl" />
                                )
                            ))}
                        </li>
                        {firstLineText && (
                            <li className="break-words max-w-[300px] text-center">
                                {/** TODO: Move formatting to the callers. */}
                                {getEntityPrettyName(firstLineText)}
                            </li>
                        )}
                        {secondLineText && (
                            <li>
                                {secondLineText}
                            </li>
                        )}
                    </ul>
                </div>
            </InteractiveWrapper>
        </div>
    )
}

interface InteractiveWrapperProps {
    to?: string | Navigable
    onClick?: () => void
    className?: string
    children: React.ReactNode
}

function InteractiveWrapper({ to, ...props }: InteractiveWrapperProps) {
    if (!to) {
        return <div {...props} />
    }

    if (typeof to === "string") {
        return <Link to={to} {...props} />
    }

    return <AppLink to={to} {...props} />
}