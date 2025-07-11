import { createContext, useContext, useEffect, useState } from "react"
import { getToken, onMessage } from "firebase/messaging"
import { messaging } from "../lib/firebase"
import { useApi } from "../hooks/useApi"
import { useAuth } from "./AuthContext"

const NotificationContext = createContext()

export const NotificationProvider = ({ children }) => {
    const { isAdmin } = useAuth()
    const { createDevice } = useApi()

    const [messages, setMessages] = useState([])
    const [fcmToken, setFcmToken] = useState(null)

    useEffect(() => {
        if ("serviceWorker" in navigator) {
            navigator.serviceWorker.register((import.meta.env.VITE_BASE_PATH || "") + "/firebase-messaging-sw.js", { type: "module" })
                .then(registration => getToken(messaging, {
                    vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY,
                    serviceWorkerRegistration: registration
                }))
                .then(setFcmToken)
                .catch(e => console.log(e))
        }

        const unsubscribe = onMessage(messaging, payload => {
            const deserializedArgs = payload.data?.args ? JSON.parse(payload.data.args) : undefined

            setMessages(prev => [
                ...prev,
                {
                    ...payload,
                    data: {
                        ...payload.data,
                        args: deserializedArgs
                    }
                }
            ])
        })

        return () => unsubscribe()
    }, [])

    useEffect(() => {
        if (fcmToken) {
            createDevice(fcmToken)
        }
    }, [fcmToken, isAdmin])

    return (
        <NotificationContext.Provider value={{ messages }}>
            {children}
        </NotificationContext.Provider>
    )
}

export const useNotifications = () => useContext(NotificationContext)