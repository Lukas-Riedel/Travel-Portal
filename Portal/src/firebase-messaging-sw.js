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

const portalBaseUrl = __VITE_PORTAL_BASE_URL__

const app = initializeApp(firebaseConfig)
const messaging = getMessaging(app)

onBackgroundMessage(messaging, payload => {
    console.log("[firebase-messaging-sw.js] Received background message", payload)

    if (payload.data.event === "ProcessingEnded") {
        const wrappedEvent = JSON.parse(payload.data.args)
        if (wrappedEvent.name === "PhotosUploadingTriggered") {
            self.registration.showNotification("Fotky byly nahrány", {
                body: "Místo " + wrappedEvent.args.placeName + " má nové fotky",
                icon: "icon-192.png",
                data: wrappedEvent
            })
        }
        else if (wrappedEvent.name === "PhotoReplacingTriggered") {
            self.registration.showNotification("Fotka byla nahrazena", {
                body: "Místo " + wrappedEvent.args.placeName + " má novou fotku",
                icon: "icon-192.png",
                data: wrappedEvent
            })
        }
    }
})

self.addEventListener("notificationclick", function(event) {
    if (event.notification.data.name === "PhotosUploadingTriggered") {
        event.notification.close()
        event.waitUntil(
            clients.openWindow("/place/" + event.notification.data.args.placeId)
        )
    }
        else if (event.notification.data.name === "PhotoReplacingTriggered") {
        event.notification.close()
        event.waitUntil(
            clients.openWindow(portalBaseUrl + "/place/" + event.notification.data.args.placeId)
        )
    }
})
