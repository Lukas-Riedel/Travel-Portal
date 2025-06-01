import React from "react"
import ReactDOM from "react-dom/client"
import App from "./App.jsx"
import "./index.css"
import { AuthProvider } from "./contexts/AuthContext"
import { ConfigurationProvider } from "./contexts/ConfigContext"
import { hydrate, QueryClient, QueryClientProvider } from "@tanstack/react-query"
import { persistQueryClient } from "@tanstack/react-query-persist-client"

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            cacheTime: 1000 * 60 * 60,
            staleTime: 1000 * 60 * 60,
        },
    },
})

const persister = {
    persistClient: async (client) => {
        localStorage.setItem("REACT_QUERY_OFFLINE_CACHE", JSON.stringify(client))
    },
    restoreClient: async () => {
        const cache = localStorage.getItem("REACT_QUERY_OFFLINE_CACHE")
        return cache ? JSON.parse(cache) : undefined
    },
    removeClient: async () => {
        localStorage.removeItem("REACT_QUERY_OFFLINE_CACHE")
    },
}

;(async () => {
    const restoredState = await persister.restoreClient()
    if (restoredState) {
        hydrate(queryClient, restoredState)
    }

    persistQueryClient({
        queryClient,
        persister,
        maxAge: 1000 * 60 * 60,
    })

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
    )
})()
