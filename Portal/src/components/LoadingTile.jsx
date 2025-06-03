import { TailSpin } from "react-loader-spinner";

// TODO: Remove and replace <LoadingTile/> by <PhotoTile/>?
export default function LoadingTile() {
    return (
        <div
            className="relative w-[350px] h-[233px] mx-auto flex items-center justify-center">
            <TailSpin
                color="black"
                height={30}
                width={30}
            />
        </div>
    )
}