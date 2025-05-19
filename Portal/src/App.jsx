import Place from "./pages/Place"
import MainLayout from "./layouts/MainLayout"
import { BrowserRouter, Route, Routes } from "react-router-dom"

export default function App() {
    return (
        <BrowserRouter basename={import.meta.env.VITE_BASE_PATH || "/"}>
            <Routes>
                <Route path="/place/:placeId" element={<MainLayout><Place /></MainLayout>} />
            </Routes>
        </BrowserRouter>
    )
}