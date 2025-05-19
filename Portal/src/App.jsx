import Place from "./pages/Place"
import MainLayout from "./layouts/MainLayout"
import { BrowserRouter, Route, Routes } from "react-router-dom"
import { useAuth } from "./contexts/AuthContext"
import { useEffect } from "react"

export default function App() {
    const { accessToken, login } = useAuth()

    useEffect(() => {
        if (!accessToken) {
            login({ username: "guest", password: "guest" })
        }
    }, [accessToken, login])

    if (!accessToken) {
        return
    }

    return (
        <BrowserRouter basename={import.meta.env.VITE_BASE_PATH || "/"}>
            <Routes>
                <Route path="/place/:placeId" element={<MainLayout><Place /></MainLayout>} />
            </Routes>
        </BrowserRouter>
    )
}