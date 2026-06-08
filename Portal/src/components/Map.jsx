import { useEffect, useRef } from "react"
import { GoogleMap, Marker, Polyline, useJsApiLoader } from "@react-google-maps/api"
import { TailSpin } from "react-loader-spinner"

const defaultMapZoom = 8
const markerPath = "M 21.7691 46.7696 H 15.923 V 0 h 5.8461 V 46.7696 z M 45.1542 11.6925 L 56.8465 0 H 24.6924 v 23.3848 h 32.1542 L 45.1542 11.6925 z"
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

export default function Map({ points, lines, geoJsons, onClick, onRightClick }) {
    const markersRef = useRef([])
    const mapRef = useRef(null)

    const onMarkerLoad = marker => {
        markersRef.current.push(marker)
    }

    const computeMarkerScale = zoom => Math.min(Math.max(0.1 + zoom * 0.1, 0.5), 0.9)
    const computeStrokeWeight = zoom => Math.min(Math.max(zoom * 0.1 - 0.1, 0.3), 1.3)

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

    const fitBounds = () => {
        if (!mapRef.current) {
            return
        }

        const bounds = new window.google.maps.LatLngBounds()
        points?.forEach(point => {
            bounds.extend(new window.google.maps.LatLng(point.latitude, point.longitude))
        })

        mapRef.current.fitBounds(bounds)
    }

    const initMap = map => {
        map.data.forEach(f => map.data.remove(f))

        if (geoJsons && geoJsons.length > 0) {
            const bounds = new google.maps.LatLngBounds()

            geoJsons.forEach(geoJson => {
                map.data.addGeoJson(geoJson)
            })

            if (onClick) {
                map.data.addListener("click", event => {
                    onClick(event.feature.getProperty("id"))
                })
            }

            map.data.setStyle(feature => {
                const color = feature.getProperty("color") || "#4285F4"
                const fillOpacity = feature.getProperty("fillOpacity") || 0.3
                const strokeWeight = feature.getProperty("strokeWeight") || 1

                return {
                    fillColor: color,
                    fillOpacity: fillOpacity,
                    strokeColor: color,
                    strokeWeight: strokeWeight
                }
            })

            map.data.forEach(feature => {
                feature.getGeometry().forEachLatLng(latlng => bounds.extend(latlng))
            })
            map.fitBounds(bounds)
        }
        else if (points?.length == 1) {
            map.setCenter({
                lat: points[0]?.latitude ?? 0,
                lng: points[0]?.longitude ?? 0
            })
            map.setZoom(defaultMapZoom)
        }
        else {
            fitBounds()
        }
    }

    const onMapLoad = map => {
        mapRef.current = map
        initMap(map)
    }

    useEffect(() => {
        if (mapRef.current) {
            initMap(mapRef.current)
        }
    }, [points?.length, lines?.length, geoJsons?.length])

    const { isLoaded } = useJsApiLoader({
        googleMapsApiKey: window.env?.VITE_FRONTEND_GOOGLE_MAPS_API_KEY || import.meta.env.VITE_FRONTEND_GOOGLE_MAPS_API_KEY,
        language: "cs",
        region: "CZ"
    })

    return (points || geoJsons) ? (
        <>
            {isLoaded && (
                <div className="w-full h-full overflow-hidden rounded-lg shadow">
                    <GoogleMap
                        mapContainerStyle={{ width: "100%", height: "100%" }}
                        onLoad={onMapLoad}
                        onZoomChanged={onMapZoomChanged}
                        onRightClick={e => onRightClick(e.latLng.lat(), e.latLng.lng())}
                        options={{ styles: mapStyles, disableDefaultUI: true, fullscreenControl: true }}>
                        {points?.map((point, index) => (
                            <Marker
                                key={index}
                                onLoad={onMarkerLoad}
                                onClick={point.onClick}
                                position={{ lat: point?.latitude, lng: point?.longitude }}
                                title={point?.name}
                                icon={{
                                    path: markerPath,
                                    strokeOpacity: point?.color === "#FFFFFF" ? 1 : 0.3,
                                    strokeWeight: computeStrokeWeight(mapRef.current?.getZoom() ?? defaultMapZoom),
                                    strokeColor: "black",
                                    fillColor: point?.color,
                                    fillOpacity: 1,
                                    rotation: 0,
                                    scale: computeMarkerScale(mapRef.current?.getZoom() ?? defaultMapZoom),
                                    anchor: new google.maps.Point(19, 52)
                                }}
                            />
                        )
                        )}
                        {lines?.map((line, index) => (
                            <Polyline
                                key={index}
                                path={[
                                    { lat: line.from.latitude, lng: line.from.longitude },
                                    { lat: line.to.latitude, lng: line.to.longitude }
                                ]}
                                options={{
                                    geodesic: true,
                                    strokeColor: line.color,
                                    strokeOpacity: line.opacity ?? 1,
                                    strokeWeight: 2
                                }} />
                        ))}
                    </GoogleMap>
                </div>
            )}
        </>
    ) : (
        <div className="flex items-center justify-center w-full [aspect-ratio:3/2]">
            <TailSpin
                color="black"
                height={80}
                width={80} />
        </div>
    )
}