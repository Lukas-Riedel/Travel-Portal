import { GoogleMap, Marker, useJsApiLoader } from "@react-google-maps/api"

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

export default function Map({ zoom, latitude, longitude, color }) {
    const { isLoaded } = useJsApiLoader({
        googleMapsApiKey: "AIzaSyCIfGRMxmDVC5WGyXWPKJOeC8GwtLgKleE",
        language: "cs",
        region: "CZ"
    });

    if (!isLoaded) {
        return null
    }

    return (
        <GoogleMap
            mapContainerStyle={{ width: "100%", height: "100%" }}
            center={{ lat: latitude, lng: longitude }}
            zoom={zoom}
            options={{ styles: mapStyles, disableDefaultUI: true, fullscreenControl: true }}>
            <Marker
                position={{ lat: latitude, lng: longitude }}
                icon={{
                    path: markerPath,
                    strokeOpacity: color === "#FFFFFF" ? 1 : 0.3,
                    strokeWeight: 1,
                    strokeColor: "black",
                    fillColor: color,
                    fillOpacity: 1,
                    rotation: 0,
                    scale: 1,
                    anchor: new google.maps.Point(19, 52)
                }}
            />
        </GoogleMap>
    )
}