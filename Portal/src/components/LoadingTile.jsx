import { TailSpin } from "react-loader-spinner";

// TODO: Use clsx as in Card.tsx
export default function LoadingTile({ className = "w-[350px] h-[233px]" }) {
    return (
        <div className={`relative mx-auto flex items-center justify-center ${className}`}>
            <TailSpin
                color="black"
                height={30}
                width={30} />
        </div>
    )
}