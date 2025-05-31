import React from "react"
import ReactDOM from "react-dom/client"
import App from "./App.jsx"
import "./index.css"
import { AuthProvider } from "./contexts/AuthContext"
import { ConfigurationProvider } from "./contexts/ConfigContext"
import { QueryClient, QueryClientProvider } from "@tanstack/react-query"

const queryClient = new QueryClient()

ReactDOM.createRoot(document.getElementById("root")).render(
    <React.StrictMode>
        <QueryClientProvider client={queryClient}>
            <AuthProvider>
                <ConfigurationProvider>
                    <App />
                </ConfigurationProvider>
            </AuthProvider>
        </QueryClientProvider>
    </React.StrictMode>
);