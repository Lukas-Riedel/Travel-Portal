import { TailSpin } from "react-loader-spinner"
import Card from "./Card.tsx"

export default function LoadingCard() {
    return (
        <Card className="h-[150px]">
            <TailSpin
                color="black"
                height={30}
                width={30} />
        </Card>
    )
}