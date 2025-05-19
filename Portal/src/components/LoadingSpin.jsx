import { TailSpin } from "react-loader-spinner"

export default function LoadingSpin() {
    return (
        <div style={{
            position: "fixed",
            top: 0,
            left: 0,
            width: "100vw",
            height: "100vh",
            backgroundColor: "rgba(0, 0, 0, 0.6)",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            zIndex: 9999
        }}>
            <TailSpin
                color="#ffffff"
                height={80}
                width={80} />
        </div>
    )
}