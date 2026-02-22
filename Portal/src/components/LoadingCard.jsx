import { TailSpin } from "react-loader-spinner";

export default function LoadingCard() {
    return (
        <div className="fbg-white rounded-xl shadow p-4 flex flex-col items-center justify-center h-[150px]">
            <TailSpin
                color="black"
                height={30}
                width={30} />
        </div>
    )
}