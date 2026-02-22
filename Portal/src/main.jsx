import React from "react"
import ReactDOM from "react-dom/client"
import App from "./App.jsx"
import "./index.css"
import "./i18n"
import { AuthProvider } from "./contexts/AuthContext"
import { ConfigurationProvider } from "./contexts/ConfigContext"
import { hydrate, QueryClient, QueryClientProvider } from "@tanstack/react-query"
import { persistQueryClient } from "@tanstack/react-query-persist-client"
import localforage from "localforage"
import { NotificationProvider } from "./contexts/NotificationContext.jsx"
import { LocationProvider } from "./contexts/LocationContext.jsx"

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            cacheTime: 1000 * 60 * 60 * 24,
            staleTime: 1000 * 60 * 60 * 24,
        },
    },
})

const persister = {
    persistClient: async (client) => {
        await localforage.setItem("REACT_QUERY_OFFLINE_CACHE", client)
    },
    restoreClient: async () => {
        return await localforage.getItem("REACT_QUERY_OFFLINE_CACHE")
    },
    removeClient: async () => {
        await localforage.removeItem("REACT_QUERY_OFFLINE_CACHE")
    },
}

    ; (async () => {
        const restoredState = await persister.restoreClient()
        if (restoredState) {
            hydrate(queryClient, restoredState)
        }

        persistQueryClient({
            queryClient,
            persister,
            maxAge: 1000 * 60 * 60 * 24,
        })

        ReactDOM.createRoot(document.getElementById("root")).render(
            <React.StrictMode>
                <QueryClientProvider client={queryClient}>
                    <AuthProvider>
                        <ConfigurationProvider>
                            <NotificationProvider>
                                <LocationProvider>
                                    <App />
                                </LocationProvider>
                            </NotificationProvider>
                        </ConfigurationProvider>
                    </AuthProvider>
                </QueryClientProvider>
            </React.StrictMode>
        )
    })()
