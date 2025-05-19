import { useRef, useEffect } from "react"
import { GoogleMap, Marker, useJsApiLoader } from "@react-google-maps/api"

export default function Map({ points }) {
    const defaultMapZoom = 8

    const mapStyles = [
        {
            "featureType": "administrative",
            "elementType": "labels.text.fill",
            "stylers": [
                {
                    "color": "#444444"
                }
            ]
        },
        {
            "featureType": "landscape",
            "elementType": "all",
            "stylers": [
                {
                    "color": "#f2f2f2"
                }
            ]
        },
        {
            "featureType": "poi",
            "elementType": "all",
            "stylers": [
                {
                    "visibility": "off"
                }
            ]
        },
        {
            "featureType": "road",
            "elementType": "all",
            "stylers": [
                {
                    "saturation": -100
                },
                {
                    "lightness": 45
                }
            ]
        },
        {
            "featureType": "road.highway",
            "elementType": "all",
            "stylers": [
                {
                    "visibility": "simplified"
                }
            ]
        },
        {
            "featureType": "road.highway",
            "elementType": "geometry.fill",
            "stylers": [
                {
                    "color": "#ffffff"
                }
            ]
        },
        {
            "featureType": "road.arterial",
            "elementType": "labels.icon",
            "stylers": [
                {
                    "visibility": "off"
                }
            ]
        },
        {
            "featureType": "transit",
            "elementType": "all",
            "stylers": [
                {
                    "visibility": "off"
                }
            ]
        },
        {
            "featureType": "water",
            "elementType": "all",
            "stylers": [
                {
                    "color": "#dde6e8"
                },
                {
                    "visibility": "on"
                }
            ]
        }
    ]

    const markerPath = "M 21.7691 46.7696 H 15.923 V 0 h 5.8461 V 46.7696 z M 45.1542 11.6925 L 56.8465 0 H 24.6924 v 23.3848 h 32.1542 L 45.1542 11.6925 z"

    const markersRef = useRef([])
    const onMarkerLoad = marker => {
        markersRef.current.push(marker)
    }

    const mapRef = useRef(null)
    const onMapLoad = map => {
        mapRef.current = map
    }

    const onMapZoomChanged = () => {
        if (!mapRef.current) {
            return
        }

        markersRef.current.forEach(marker => {
            marker.icon.scale = computeMarkerScale(mapRef.current.getZoom())
            marker.icon.strokeWeight = computeStrokeWeight(mapRef.current.getZoom())
            marker.setIcon(marker.icon)
        })
    }

    const computeMarkerScale = zoom => Math.min(Math.max(0.1 + zoom * 0.1, 0.5), 0.9)
    const computeStrokeWeight = zoom => Math.min(Math.max(zoom * 0.1 - 0.1, 0.3), 1.3)

    const { isLoaded } = useJsApiLoader({
        googleMapsApiKey: import.meta.env.VITE_GOOGLE_MAPS_API_KEY,
        language: "cs",
        region: "CZ"
    })

    useEffect(() => {
        if (!mapRef.current || points.length < 2) {
            return
        }

        const bounds = new window.google.maps.LatLngBounds()
        points.forEach(point => {
            bounds.extend(new window.google.maps.LatLng(point.latitude, point.longitude))
        })

        mapRef.current.fitBounds(bounds)
    }, [mapRef, points])

    if (!isLoaded) {
        return null
    }

    return (
        <GoogleMap
            mapContainerStyle={{ width: "100%", height: "100%" }}
            onLoad={onMapLoad}
            onZoomChanged={onMapZoomChanged}
            zoom={defaultMapZoom}
            center={{ lat: points[0]?.latitude ?? 0, lng: points[0]?.longitude ?? 0 }}
            options={{ styles: mapStyles, disableDefaultUI: true, fullscreenControl: true }}>
            {points.map((point, index) => (
                <Marker
                    key={index}
                    onLoad={onMarkerLoad}
                    onClick={point.onClick}
                    position={{ lat: point.latitude, lng: point.longitude }}
                    title={point.name}
                    icon={{
                        path: markerPath,
                        strokeOpacity: point.color === "#FFFFFF" ? 1 : 0.3,
                        strokeWeight: computeStrokeWeight(mapRef.current?.getZoom() ?? defaultMapZoom),
                        strokeColor: "black",
                        fillColor: point.color,
                        fillOpacity: 1,
                        rotation: 0,
                        scale: computeMarkerScale(mapRef.current?.getZoom() ?? defaultMapZoom),
                        anchor: new google.maps.Point(19, 52)
                    }}
                />
            )
            )}
        </GoogleMap>
    )
}