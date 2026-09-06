import { initializeApp } from "firebase/app"
import { getMessaging } from "firebase/messaging"

const firebaseConfig = {
    apiKey: window.env?.VITE_FIREBASE_API_KEY || import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: window.env?.VITE_FIREBASE_AUTH_DOMAIN || import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: window.env?.VITE_FIREBASE_PROJECT_ID || import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: window.env?.VITE_FIREBASE_STORAGE_BUCKET || import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: window.env?.VITE_FIREBASE_MESSAGING_SENDER_ID || import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: window.env?.VITE_FIREBASE_APP_ID || import.meta.env.VITE_FIREBASE_APP_ID,
    measurementId: import.meta.env.VITE_FIREBASE_MEASUREMENT_ID
}

const app = initializeApp(firebaseConfig)

const messaging = getMessaging(app)

export { messaging }
