import { initializeApp } from "firebase/app"
import { getMessaging, onBackgroundMessage } from "firebase/messaging/sw"

const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
    measurementId: import.meta.env.VITE_FIREBASE_MEASUREMENT_ID,
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
                icon: "/icon.png"
            })
        }
        else if (wrappedEvent.name === "PhotoReplacingTriggered") {
            self.registration.showNotification("Nahrazování fotky bylo dokončeno.", {
                body: "",
                icon: "/icon.png"
            })
        }
    }
})
