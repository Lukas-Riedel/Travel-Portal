import { TailSpin } from "react-loader-spinner"
import BarItem from "./BarItem"

export default function LoadingBarItem() {
    return (
        <BarItem>
            <div className="mx-4 min-w-[36px] min-h-[24px] flex items-center justify-center">
                <TailSpin
                    color="black"
                    height={16}
                    width={16} />
            </div>
        </BarItem>
    )
}