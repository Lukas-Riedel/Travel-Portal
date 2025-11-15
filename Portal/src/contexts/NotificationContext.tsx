import { createContext, useContext, useEffect, useState } from "react"
import { getToken, onMessage } from "firebase/messaging"
import { messaging } from "../lib/firebase.js"
import { useAuth } from "./AuthContext.tsx"
import { useConfiguration } from "./ConfigContext.tsx"
import { createDevice } from "../clients/coreClient.ts"
import type { UseNotificationsResult } from "../types/UseNotificationsResult.ts"
import type { Message } from "../types/Message.ts"

const NotificationContext = createContext<UseNotificationsResult | undefined>(undefined)

export const NotificationProvider = ({ children }: { children: React.ReactNode }) => {
    const { accessToken } = useAuth()
    const { deviceId } = useConfiguration()

    const [messages, setMessages] = useState<Message[]>([])
    const [fcmToken, setFcmToken] = useState<string | null>(null)

    useEffect(() => {
        if ("serviceWorker" in navigator) {
            const swVersion = import.meta.env.VITE_SW_VERSION || Date.now()
            navigator.serviceWorker.register("/firebase-messaging-sw.js?v=" + swVersion)
                .then(registration => getToken(messaging, {
                    vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY,
                    serviceWorkerRegistration: registration
                }))
                .then(setFcmToken)
        }

        const unsubscribe = onMessage(messaging, payload => {
            const deserializedArgs = payload.data?.args ? JSON.parse(payload.data.args) : undefined

            setMessages(previous => [
                ...previous,
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
            createDevice(deviceId, { fcmToken })
        }
    }, [fcmToken, accessToken])

    return (
        <NotificationContext.Provider value={{ messages }}>
            {children}
        </NotificationContext.Provider>
    )
}

export const useNotifications = (): UseNotificationsResult => useContext(NotificationContext)