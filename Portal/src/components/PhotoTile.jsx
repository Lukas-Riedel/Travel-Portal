import { Link } from "react-router-dom";
import { getPrettyName } from "../utils/helpers";
import LoadingTile from "./LoadingTile";

export default function PhotoTile({ src, firstLineText, secondLineText, categories, to, onClick }) {
    const InteractiveElement = to ? Link : "div"
    return src ? (
        <div className="relative w-[350px] h-[233px] mx-auto">
            <InteractiveElement
                to={to}
                onClick={onClick}
                className="block cursor-pointer">
                <img
                    src={src}
                    alt={firstLineText ?? ""}
                    className="w-[350px] h-[233px] object-cover brightness-100 hover:brightness-50 transition duration-700 ease-in-out rounded-xl"
                />
                <div className="absolute left-0 bottom-0 w-full flex items-center justify-center bg-gradient-to-t from-black via-black/70 to-transparent text-white text-sm uppercase font-medium leading-[170%] py-4 rounded-b-xl">
                    <ul className="list-none m-0 p-0 flex flex-col items-center gap-0.5 text-base">
                        <li className="flex flex-row items-center gap-1">
                            {categories?.map((category, index) => (
                                category?.metadata?.unicode && (
                                    <img
                                        key={index}
                                        src={`/img/flags/${category.metadata.unicode}.svg`}
                                        alt={category?.name}
                                        className="w-4 h-4 align-middle object-cover rounded-xl" />
                                )

                            ))}
                        </li>
                        <li>
                            {getPrettyName(firstLineText)}
                        </li>
                        <li>
                            {secondLineText}
                        </li>
                    </ul>
                </div>
            </InteractiveElement>
        </div>
    ) : (
        <LoadingTile />
    )
}