import Place from "./pages/Place"
import MainLayout from "./layouts/MainLayout"
import { BrowserRouter, Route, Routes, useSearchParams } from "react-router-dom"
import { useAuth } from "./contexts/AuthContext"
import { useEffect } from "react"
import { Toaster } from "sonner"
import Category from "./pages/Category"

function AppContent() {
    const { accessToken, login } = useAuth()
    const [searchParams] = useSearchParams()

    useEffect(() => {
        const apiKey = searchParams.get("apiKey")
        if (apiKey) {
            login({ apiKey })
        }

        if (!accessToken) {
            login({ username: "guest", password: "guest" })

        }
    }, [accessToken, login, searchParams])

    if (!accessToken) {
        return
    }

    return (
        <Routes>
            <Route path="/place/:placeId" element={<MainLayout><Place /></MainLayout>} />
            <Route path="/category/:categoryId" element={<MainLayout><Category /></MainLayout>} />
        </Routes>
    )
}

export default function App() {
    return (
        <>
            <Toaster position="top-center" offset={96} />
            <BrowserRouter basename={import.meta.env.VITE_BASE_PATH || "/"}>
                <AppContent />
            </BrowserRouter>
        </>
    )
}