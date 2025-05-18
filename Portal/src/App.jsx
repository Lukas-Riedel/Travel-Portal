import Place from "./component/Place"
import { BrowserRouter, Route, Routes } from "react-router-dom"

export default function App() {
    return (
        <BrowserRouter basename="/new">
            <Routes>
                <Route path="/place/:id" element={<Place />} />
            </Routes>
        </BrowserRouter>
    )
}