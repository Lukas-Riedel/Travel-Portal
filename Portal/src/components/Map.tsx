import { useEffect, useRef, useState } from "react"
import { GoogleMap, useJsApiLoader } from "@react-google-maps/api"
import { TailSpin } from "react-loader-spinner"
import { GoogleMapsOverlay } from "@deck.gl/google-maps"
import { IconLayer, LineLayer } from "@deck.gl/layers"
import type { Layer } from "@deck.gl/core"
import type { MapPoint } from "../types/MapPoint.ts"
import type { MapLine } from "../types/MapLine.ts"
import type { GeoJSON } from "geojson"
import { getEntityPrettyName } from "../utils/formattingUtils.ts"

const DEFAULT_MAP_ZOOM = 8
const DEFAULT_GEOJSON_COLOR = "#4285F4"
const MARKER_SIZE_MULTIPLIER = 55
const MAP_STYLES = [
    { "featureType": "administrative", "elementType": "labels.text.fill", "stylers": [{ "color": "#444444" }] },
    { "featureType": "landscape", "elementType": "all", "stylers": [{ "color": "#f2f2f2" }] },
    { "featureType": "poi", "elementType": "all", "stylers": [{ "visibility": "off" }] },
    { "featureType": "road", "elementType": "all", "stylers": [{ "saturation": -100 }, { "lightness": 45 }] },
    { "featureType": "road.highway", "elementType": "all", "stylers": [{ "visibility": "simplified" }] },
    { "featureType": "road.highway", "elementType": "geometry.fill", "stylers": [{ "color": "#ffffff" }] },
    { "featureType": "road.arterial", "elementType": "labels.icon", "stylers": [{ "visibility": "off" }] },
    { "featureType": "transit", "elementType": "all", "stylers": [{ "visibility": "off" }] },
    { "featureType": "water", "elementType": "all", "stylers": [{ "color": "#dde6e8" }, { "visibility": "on" }] }
]

const MARKER_SVG_CACHE = new globalThis.Map<string, string>()

interface MapProps {
    points?: MapPoint[]
    lines?: MapLine[]
    geoJsons?: GeoJSON[]
    onClick?: (featureId?: string) => Promise<void>
    onRightClick?: (latitude: number, longitude: number) => Promise<void>
}

export default function Map({ points, lines, geoJsons, onClick, onRightClick }: MapProps) {
    const mapRef = useRef<google.maps.Map>(null)
    const overlayRef = useRef<GoogleMapsOverlay>(null)
    const layersRef = useRef<Layer[]>([])

    const [hoveredMarkerData, setHoveredMarkerData] = useState<{ name: string, x: number, y: number, unicode?: string } | null>(null)
    const [isOverlayReady, setIsOverlayReady] = useState(false)
    const [zoom, setZoom] = useState(DEFAULT_MAP_ZOOM)

    const computeMarkerScale = (zoom: number) => Math.min(Math.max(0.1 + zoom * 0.1, 0.5), 0.9)
    const hexToRgba = (hex: string, alpha: number = 255): [number, number, number, number] => {
        const pureHex = hex.replace("#", "")
        return [
            parseInt(pureHex.substring(0, 2), 16),
            parseInt(pureHex.substring(2, 4), 16),
            parseInt(pureHex.substring(4, 6), 16),
            alpha
        ]
    }

    const getMarkerSvg = (color: string): string => {
        if (MARKER_SVG_CACHE.has(color)) {
            return MARKER_SVG_CACHE.get(color)!
        }

        const svgData = `
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="60"
                height="60"
                viewBox="-2 -2 61 61">
                <path
                    d="M 21.7691 46.7696 H 15.923 V 0 h 5.8461 V 46.7696 z M 45.1542 11.6925 L 56.8465 0 H 24.6924 v 23.3848 h 32.1542 L 45.1542 11.6925 z"
                    fill="${color}"
                    stroke="black"
                    stroke-width="0.1"
                    stroke-linejoin="round" />
            </svg>
            `

        const svgDataUrl = `data:image/svg+xml;utf8,${encodeURIComponent(svgData)}`
        MARKER_SVG_CACHE.set(color, svgDataUrl)

        return svgDataUrl
    }
    useEffect(() => {
        if (!overlayRef.current || !(points || lines) || !isOverlayReady) {
            return
        }

        const activeLayers: Layer[] = []
        if (lines && lines.length > 0) {
            activeLayers.push(
                new LineLayer({
                    id: "deckgl-lines",
                    data: lines,
                    pickable: false,
                    getSourcePosition: line => [line.from.longitude, line.from.latitude],
                    getTargetPosition: line => [line.to.longitude, line.to.latitude],
                    getColor: line => hexToRgba(line.color),
                    getWidth: () => 2,
                    widthUnits: "pixels"
                })
            )
        }

        if (points && points.length > 0) {
            const scale = computeMarkerScale(zoom)
            activeLayers.push(
                new IconLayer({
                    id: "deckgl-markers",
                    data: points,
                    pickable: true,
                    getIcon: point => {
                        return {
                            url: getMarkerSvg(point.color),
                            width: 60,
                            height: 60,
                            anchorX: 21,
                            anchorY: 49
                        }
                    },
                    getPosition: point => [point.longitude, point.latitude],
                    getSize: () => MARKER_SIZE_MULTIPLIER * scale,
                    onClick: info => {
                        if (info.object && info.object.onClick) {
                            info.object.onClick()
                        }
                    },
                    onHover: info => {
                        if (info.object) {
                            setHoveredMarkerData({
                                name: info.object.name,
                                unicode: info.object.unicode,
                                x: info.x,
                                y: info.y
                            })
                        }
                        else {
                            setHoveredMarkerData(null)
                        }
                    },
                    updateTriggers: {
                        getSize: [scale]
                    },
                }))
        }

        layersRef.current = activeLayers
        overlayRef.current.setProps({ layers: activeLayers })
    }, [points, lines, setHoveredMarkerData, isOverlayReady, zoom])

    const onMapZoomChanged = () => {
        if (!mapRef.current || !overlayRef.current) {
            return
        }

        setZoom(mapRef.current.getZoom())
    }

    const initMap = (map: google.maps.Map) => {
        map.data.forEach(f => map.data.remove(f))
        if (window.google?.maps?.event) {
            window.google.maps.event.clearListeners(map.data, "click")
        }

        const bounds = new window.google.maps.LatLngBounds()
        let hasDataForBounds = false

        // TODO: Rewrite to deck.gl.
        if (geoJsons && geoJsons.length > 0) {
            geoJsons.forEach(geoJson => {
                map.data.addGeoJson(geoJson)
            })

            map.data.setStyle(feature => {
                const color = (feature.getProperty("color") as string) || DEFAULT_GEOJSON_COLOR
                return {
                    fillColor: color,
                    fillOpacity: (feature.getProperty("fillOpacity") as number) || 0.3,
                    strokeColor: color,
                    strokeWeight: (feature.getProperty("strokeWeight") as number) || 1
                }
            })

            map.data.forEach(feature => {
                feature.getGeometry().forEachLatLng(latlng => {
                    bounds.extend(latlng)
                    hasDataForBounds = true
                })
            })

            if (onClick) {
                map.data.addListener("click", event => {
                    onClick(event.feature.getProperty("id") as string)
                })
            }
        }

        if (points && points.length > 0) {
            points.forEach(point => {
                bounds.extend(new window.google.maps.LatLng(point.latitude, point.longitude))
                hasDataForBounds = true
            })
        }

        if (points?.length === 1 && (!geoJsons || geoJsons.length === 0)) {
            map.setCenter({
                lat: points[0]?.latitude ?? 0,
                lng: points[0]?.longitude ?? 0
            })
            map.setZoom(DEFAULT_MAP_ZOOM)
        }
        else if (hasDataForBounds) {
            map.fitBounds(bounds)
        }
    }

    const onMapLoad = (map: google.maps.Map) => {
        mapRef.current = map

        const overlay = new GoogleMapsOverlay({
            layers: []
        })

        overlay.setMap(map)
        overlayRef.current = overlay

        initMap(map)
        setIsOverlayReady(true)
    }

    useEffect(() => {
        if (mapRef.current) {
            initMap(mapRef.current)
        }
    }, [points?.length, lines?.length, geoJsons?.length])

    useEffect(() => {
        return () => {
            if (overlayRef.current) {
                overlayRef.current.setMap(null)
            }
        }
    }, [])

    const { isLoaded } = useJsApiLoader({
        googleMapsApiKey: window.env?.VITE_FRONTEND_GOOGLE_MAPS_API_KEY || import.meta.env.VITE_FRONTEND_GOOGLE_MAPS_API_KEY,
        // TODO: Use i18n from useTranslation?
        language: "cs",
        region: "CZ"
    })

    return (points || geoJsons) ? (
        <>
            {isLoaded && (
                <div className="relative w-full h-full overflow-hidden rounded-lg shadow">
                    <GoogleMap
                        mapContainerStyle={{ width: "100%", height: "100%" }}
                        onLoad={onMapLoad}
                        onZoomChanged={onMapZoomChanged}
                        onRightClick={e => onRightClick && onRightClick(e.latLng.lat(), e.latLng.lng())}
                        options={{ styles: MAP_STYLES, disableDefaultUI: true, fullscreenControl: true }} />
                    {hoveredMarkerData && (
                        <div style={{
                            position: "absolute",
                            left: hoveredMarkerData.x + 15,
                            top: hoveredMarkerData.y - 30,
                            pointerEvents: "none",
                            zIndex: 9999,
                            backgroundColor: "white",
                            padding: "4px 8px",
                            borderRadius: "4px",
                            boxShadow: "0 2px 4px rgba(0,0,0,0.2)",
                            fontSize: "14px",
                            fontWeight: "500",
                            border: "1px solid #ccc",
                            display: "flex",
                            alignItems: "center",
                            gap: "8px"
                        }}>
                            {hoveredMarkerData.unicode && (
                                <img
                                    className="w-5 h-5 rounded-sm object-cover"
                                    src={`/img/flags/${hoveredMarkerData.unicode}.svg`} />
                            )}
                            <span className="whitespace-nowrap">
                                {getEntityPrettyName(hoveredMarkerData.name)}
                            </span>
                        </div>
                    )}
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