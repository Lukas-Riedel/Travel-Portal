import React from "react"
import ReactDOM from "react-dom/client"
import App from "./App.jsx"
import "./index.css"
import { AuthProvider } from "./contexts/AuthContext";
import { ConfigurationProvider } from "./contexts/ConfigContext";

ReactDOM.createRoot(document.getElementById("root")).render(
    <React.StrictMode>
        <AuthProvider>
            <ConfigurationProvider>
                <App />
            </ConfigurationProvider>
        </AuthProvider>
    </React.StrictMode>
);