import { Link } from "react-router-dom"
import { TailSpin } from "react-loader-spinner"

const loadingCategoriesCount = 3

export default function CategoryBar({ categories }) {
    return (!categories || categories.length > 0) && (
        <div className="flex flex-col lg:flex-row justify-center gap-3 px-4 my-4">
            {categories ? categories.map(({ id, name }) => (
                <Link
                    key={id}
                    to={`${window.location.pathname.startsWith("/plan") ? "/plan" : ""}/category/${id}`}
                    className="flex w-full lg:w-auto text-center items-center justify-center px-4 py-2 bg-white rounded-lg shadow text-sm font-medium hover:bg-gray-100 transition">
                    {name}
                </Link>
            )) : Array.from({ length: loadingCategoriesCount }).map((_, idx) => (
                <div
                    key={idx}
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
