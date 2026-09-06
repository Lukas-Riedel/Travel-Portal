import React from "react"
import ReactDOM from "react-dom/client"
import App from "./App.jsx"
import "./index.css"
import "./i18n.js"
import { AuthProvider } from "./contexts/AuthContext.js"
import { ConfigurationProvider } from "./contexts/ConfigContext.js"
import { QueryClient, QueryClientProvider } from "@tanstack/react-query"
import { persistQueryClient } from "@tanstack/react-query-persist-client"
import localforage from "localforage"
import { createAsyncStoragePersister } from "@tanstack/query-async-storage-persister"
import { NotificationProvider } from "./contexts/NotificationContext.js"
import { LocationProvider } from "./contexts/LocationContext.js"

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            gcTime: 1000 * 60 * 60 * 24,
            staleTime: 1000 * 60 * 60 * 24
        }
    }
})

const persister = createAsyncStoragePersister({
    storage: localforage,
    key: "REACT_QUERY_OFFLINE_CACHE"
})

persistQueryClient({
    queryClient,
    persister,
    maxAge: 1000 * 60 * 60 * 24
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
