import { createContext, useContext, useEffect, useState } from "react"
import { getToken, onMessage } from "firebase/messaging"
import { messaging } from "../lib/firebase"
import { useAuth } from "./AuthContext"
import { useConfiguration } from "./ConfigContext"
import { createDevice } from "../clients/coreClient"

const NotificationContext = createContext()

export const NotificationProvider = ({ children }) => {
    const { accessToken } = useAuth()
    const { deviceId } = useConfiguration()

    const [messages, setMessages] = useState([])
    const [fcmToken, setFcmToken] = useState(null)

    useEffect(() => {
        if ("serviceWorker" in navigator) {
            const swVersion = import.meta.env.VITE_SW_VERSION || Date.now()
            navigator.serviceWorker.register((import.meta.env.VITE_BASE_PATH || "") + "/firebase-messaging-sw.js?v=" + swVersion)
                .then(registration => getToken(messaging, {
                    vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY,
                    serviceWorkerRegistration: registration
                }))
                .then(setFcmToken)
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
        if (fcmToken && accessToken) {
            createDevice(deviceId, { "fcmToken": fcmToken })
        }
    }, [fcmToken, accessToken])

    return (
        <NotificationContext.Provider value={{ messages }}>
            {children}
        </NotificationContext.Provider>
    )
}

export const useNotifications = () => useContext(NotificationContext)