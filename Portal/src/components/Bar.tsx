import LoadingBarItem from "./LoadingBarItem"

const LOADING_BAR_ITEMS_COUNT = 3

interface BarProps {
    children?: React.ReactNode
}

export default function Bar({ children }: BarProps) {
    return (
        <div className="flex flex-col lg:flex-row justify-center gap-3 px-4 my-4">
            {children || Array.from({ length: LOADING_BAR_ITEMS_COUNT }, (_, index) => (
                <LoadingBarItem key={index} />
            ))}
        </div>
    )
}