import { createContext, useContext, useEffect, useState, type ReactNode } from "react"
import { getToken, onMessage } from "firebase/messaging"
import { messaging } from "../lib/firebase.ts"
import { useAuth } from "./AuthContext.tsx"
import { useConfiguration } from "./ConfigContext.tsx"
import { createDevice } from "../clients/coreClient.ts"
import type { UseNotificationsResult } from "../types/UseNotificationsResult.ts"
import type { Message } from "../types/Message.ts"

const NotificationContext = createContext<UseNotificationsResult | undefined>(undefined)

interface NotificationProviderProps {
    children: ReactNode
}

export const NotificationProvider = ({ children }: NotificationProviderProps) => {
    const { accessToken } = useAuth()
    const { deviceId } = useConfiguration()

    const [messages, setMessages] = useState<Message[]>([])
    const [fcmToken, setFcmToken] = useState<string | null>(null)

    const fetchToken = async (registration: ServiceWorkerRegistration) => {
        const token = await getToken(messaging, {
            vapidKey: window.env?.VITE_FIREBASE_VAPID_KEY || import.meta.env.VITE_FIREBASE_VAPID_KEY,
            serviceWorkerRegistration: registration
        })

        if (token) {
            setFcmToken(token)
        }
    }

    useEffect(() => {
        if ("serviceWorker" in navigator) {
            const swVersion = window.env?.VITE_SW_VERSION || import.meta.env.VITE_SW_VERSION || Date.now()
            navigator.serviceWorker.register("/firebase-messaging-sw.js?v=" + swVersion).then(fetchToken)
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
        if (accessToken && "serviceWorker" in navigator) {
            navigator.serviceWorker.ready.then(fetchToken);
        }
    }, [accessToken])

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