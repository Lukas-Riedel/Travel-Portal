importScripts("/env.js")

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

    if (payload.data.event === "NewDataConsistencyIssuesDetected") {
        const args = JSON.parse(payload.data.args)

        self.registration.showNotification("Vyskytly se nové problémy", {
            body: "Hlášeno " + args.count + " nových problémů",
            icon: "icon-192.png",
            data: "/admin"
        })
    }

    if (payload.data.event === "FlightLogged") {
        const args = JSON.parse(payload.data.args)
        const formattedActualArrival = new Intl.DateTimeFormat(undefined, {
            hour: "2-digit",
            minute: "2-digit",
            hour12: false,
            timeZone: args.timezone
        }).format(new Date(args.actualArrival * 1000))

        self.registration.showNotification("Let přistál", {
            body: "Let " + args.flight + " přistál na letišti " + args.to + " v " + formattedActualArrival + " místního času",
            icon: "icon-192.png"
        })
    }

    if (payload.data.event === "ProcessingEnded") {
        const wrappedEvent = JSON.parse(payload.data.args)

        if (wrappedEvent.name === "PhotosUploadingTriggered") {
            self.registration.showNotification("Fotky byly nahrány", {
                body: "Místo " + wrappedEvent.args.placeName + " má nové fotky",
                icon: "icon-192.png",
                data: "/place/" + wrappedEvent.args.placeId
            })
        }
        else if (wrappedEvent.name === "PhotoReplacingTriggered") {
            self.registration.showNotification("Fotka byla nahrazena", {
                body: "Místo " + wrappedEvent.args.placeName + " má novou fotku",
                icon: "icon-192.png",
                data: "/place/" + wrappedEvent.args.placeId
            })
        }
        else if (wrappedEvent.name === "HighlightsSelectingTriggered") {
            if (wrappedEvent.args.highlightType === "place") {
                self.registration.showNotification("Highlighty byly vytvořeny", {
                    body: "Místo " + wrappedEvent.args.placeName + " má nové highlighty",
                    icon: "icon-192.png",
                    data: "/place/" + wrappedEvent.args.placeId
                })
            }
            else if (wrappedEvent.args.highlightType === "trip") {
                self.registration.showNotification("Highlighty byly vytvořeny", {
                    body: "Výlet " + wrappedEvent.args.entityName + " má nové highlighty",
                    icon: "icon-192.png",
                    data: "/trip/" + wrappedEvent.args.entityId
                })
            }
            else if (wrappedEvent.args.highlightType === "category") {
                self.registration.showNotification("Highlighty byly vytvořeny", {
                    body: "Kategorie " + wrappedEvent.args.entityName + " má nové highlighty",
                    icon: "icon-192.png",
                    data: "/category/" + wrappedEvent.args.entityId
                })
            }
            else if (wrappedEvent.args.highlightType === "year") {
                self.registration.showNotification("Highlighty byly vytvořeny", {
                    body: "Rok " + wrappedEvent.args.entityName + " má nové highlighty",
                    icon: "icon-192.png",
                    data: "/year/" + wrappedEvent.args.entityId
                })
            }
        }
    }

    if (payload.data.event === "ProcessingFailed") {
        const wrappedEvent = JSON.parse(payload.data.args)

        if (wrappedEvent.name === "PhotosUploadingTriggered") {
            self.registration.showNotification("Fotky nebyly nahrány", {
                body: "Nahrávání fotek pro místo " + wrappedEvent.args.placeName + " se nezdařilo",
                icon: "icon-192.png",
                data: "/place/" + wrappedEvent.args.placeId
            })
        }
        else if (wrappedEvent.name === "PhotoReplacingTriggered") {
            self.registration.showNotification("Fotka nebyla nahrazena", {
                body: "Nahrazování fotky pro místo " + wrappedEvent.args.placeName + " se nezdařilo",
                icon: "icon-192.png",
                data: "/place/" + wrappedEvent.args.placeId
            })
        }
        else if (wrappedEvent.name === "HighlightsSelectingTriggered") {
            if (wrappedEvent.args.highlightType === "place") {
                self.registration.showNotification("Highlighty nebyly vytvořeny", {
                    body: "Vytvoření highlightů pro místo " + wrappedEvent.args.placeName + " se nezdařilo",
                    icon: "icon-192.png",
                    data: "/place/" + wrappedEvent.args.placeId
                })
            }
            else if (wrappedEvent.args.highlightType === "trip") {
                self.registration.showNotification("Highlighty nebyly vytvořeny", {
                    body: "Vytvoření highlightů pro výlet " + wrappedEvent.args.entityName + " se nezdařilo",
                    icon: "icon-192.png",
                    data: "/trip/" + wrappedEvent.args.entityId
                })
            }
            else if (wrappedEvent.args.highlightType === "category") {
                self.registration.showNotification("Highlighty nebyly vytvořeny", {
                    body: "Vytvoření highlightů pro kategorii " + wrappedEvent.args.entityName + " se nezdařilo",
                    icon: "icon-192.png",
                    data: "/category/" + wrappedEvent.args.entityId
                })
            }
            else if (wrappedEvent.args.highlightType === "year") {
                self.registration.showNotification("Highlighty nebyly vytvořeny", {
                    body: "Vytvoření highlightů pro rok " + wrappedEvent.args.entityName + " se nezdařilo",
                    icon: "icon-192.png",
                    data: "/year/" + wrappedEvent.args.entityId
                })
            }
        }
    }
})

self.addEventListener("notificationclick", function (event) {
    event.notification.close()
    event.waitUntil(clients.openWindow(portalBaseUrl + event.notification.data))
})
