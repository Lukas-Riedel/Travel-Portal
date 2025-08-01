import { precacheAndRoute } from "workbox-precaching"
precacheAndRoute(self.__WB_MANIFEST || [])

import { initializeApp } from "firebase/app"
import { getMessaging, onBackgroundMessage } from "firebase/messaging/sw"

self.addEventListener("install", event => {
    self.skipWaiting()
})

self.addEventListener("activate", event => {
    self.clients.claim()
})

const firebaseConfig = {
    apiKey: __VITE_FIREBASE_API_KEY__,
    authDomain: __VITE_FIREBASE_AUTH_DOMAIN__,
    projectId: __VITE_FIREBASE_PROJECT_ID__,
    storageBucket: __VITE_FIREBASE_STORAGE_BUCKET__,
    messagingSenderId: __VITE_FIREBASE_MESSAGING_SENDER_ID__,
    appId: __VITE_FIREBASE_APP_ID__,
    measurementId: __VITE_FIREBASE_MEASUREMENT_ID__,
}

const app = initializeApp(firebaseConfig)
const messaging = getMessaging(app)

onBackgroundMessage(messaging, payload => {
    console.log("[firebase-messaging-sw.js] Received background message", payload)

    if (payload.data.event === "ProcessingEnded") {
        const wrappedEvent = JSON.parse(payload.data.args)
        if (wrappedEvent.name === "PhotosUploadingTriggered") {
            self.registration.showNotification("Nahrávání fotek bylo dokončeno.", {
                body: "",
                icon: "icon-192.png"
            })
        }
        else if (wrappedEvent.name === "PhotoReplacingTriggered") {
            self.registration.showNotification("Nahrazování fotky bylo dokončeno.", {
                body: "",
                icon: "icon-192.png"
            })
        }
    }
})
