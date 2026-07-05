import { Link } from "react-router-dom"
import { TailSpin } from "react-loader-spinner"
import type { Category } from "../types/CoreSwaggerTypes.ts"
import AppLink from "./AppLink.tsx"
import { getEntityPrettyName } from "../utils/formattingUtils.ts"

const LOADING_CATEGORIES_COUNT = 3

interface CategoryBarProps {
    categories: Category[] | null
}

export default function CategoryBar({ categories }: CategoryBarProps) {
    return (!categories || categories.length > 0) && (
        <div className="flex flex-col lg:flex-row justify-center gap-3 px-4 my-4">
            {categories ? categories.map(category => (
                <AppLink
                    key={category.id}
                    to={category}
                    className="flex w-full lg:w-auto text-center items-center justify-center px-4 py-2 bg-white rounded-lg shadow text-sm font-medium hover:bg-gray-100 transition">
                    {getEntityPrettyName(category.name)}
                </AppLink>
            )) : Array.from({ length: LOADING_CATEGORIES_COUNT }).map((_, index) => (
                <div
                    key={index}
                    className="flex w-full lg:w-auto text-center items-center justify-center px-4 py-2 bg-white rounded-lg shadow text-sm font-medium hover:bg-gray-100 transition">
                    <div className="mx-4 min-w-[36px] min-h-[24px] flex items-center justify-center">
                        <TailSpin
                            color="black"
                            height={16}
                            width={16} />
                    </div>
                </div>
            ))}
        </div>
    )
}
