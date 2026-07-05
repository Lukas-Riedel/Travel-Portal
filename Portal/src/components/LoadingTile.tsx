import { TailSpin } from "react-loader-spinner"
import { cn } from "../utils/formattingUtils.ts"

interface LoadingTileProps {
    className?: string
}

export default function LoadingTile({ className = "w-[350px] h-[233px]" }: LoadingTileProps) {
    return (
        <div className={cn("relative mx-auto flex items-center justify-center", className)}>
            <TailSpin
                color="black"
                height={30}
                width={30} />
        </div>
    )
}