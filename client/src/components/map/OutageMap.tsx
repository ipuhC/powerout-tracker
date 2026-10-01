import { useEffect, useRef, useState } from 'react';
import { MapContainer, TileLayer, Circle, useMap, useMapEvents, Popup } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';
import { Outage } from '../../types';

export interface OutageMapProps {
  center: [number, number];
  zoom?: number;
  outages?: Outage[];
  selectedLocation?: { lat: number; lng: number } | null;
  radiusKm?: number;
  interactive?: boolean;
  onLocationSelect?: (coords: { lat: number; lng: number }) => void;
  onBoundsChange?: (bounds: { min_lat: number; min_lng: number; max_lat: number; max_lng: number }) => void;
}

function MapEvents({ 
  interactive, 
  onLocationSelect, 
  onBoundsChange,
  setPinnedId
}: { 
  interactive?: boolean; 
  onLocationSelect?: (coords: { lat: number; lng: number }) => void;
  onBoundsChange?: (bounds: { min_lat: number; min_lng: number; max_lat: number; max_lng: number }) => void;
  setPinnedId: (id: number | null) => void;
}) {
  const map = useMapEvents({
    click(e) {
      if (interactive && onLocationSelect) {
        onLocationSelect(e.latlng);
      }
      setPinnedId(null); // Unpin if clicking on map
    },
    moveend() {
      if (onBoundsChange) {
        const bounds = map.getBounds();
        onBoundsChange({
          min_lat: bounds.getSouth(),
          min_lng: bounds.getWest(),
          max_lat: bounds.getNorth(),
          max_lng: bounds.getEast(),
        });
      }
    }
  });

  useEffect(() => {
    if (onBoundsChange) {
      const bounds = map.getBounds();
      onBoundsChange({
        min_lat: bounds.getSouth(),
        min_lng: bounds.getWest(),
        max_lat: bounds.getNorth(),
        max_lng: bounds.getEast(),
      });
    }
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return null;
}

function CenterUpdater({ center, zoom }: { center: [number, number]; zoom: number }) {
  const map = useMap();
  const prevCenter = useRef(center);
  const prevZoom = useRef(zoom);

  useEffect(() => {
    if (prevCenter.current[0] !== center[0] || prevCenter.current[1] !== center[1] || prevZoom.current !== zoom) {
      map.setView(center, zoom);
      prevCenter.current = center;
      prevZoom.current = zoom;
    }
  }, [center, zoom, map]);

  return null;
}

export function OutageMap({
  center,
  zoom = 13,
  outages = [],
  selectedLocation,
  radiusKm,
  interactive = false,
  onLocationSelect,
  onBoundsChange
}: OutageMapProps) {
  const [pinnedId, setPinnedId] = useState<number | null>(null);

  return (
    <div className="w-full h-full relative z-0">
      <MapContainer 
        center={center} 
        zoom={zoom} 
        style={{ width: '100%', height: '100%' }}
        dragging={interactive}
        scrollWheelZoom={interactive}
        doubleClickZoom={interactive}
      >
        <TileLayer
          attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />
        
        <CenterUpdater center={center} zoom={zoom} />
        
        <MapEvents 
          interactive={interactive} 
          onLocationSelect={onLocationSelect} 
          onBoundsChange={onBoundsChange}
          setPinnedId={setPinnedId}
        />

        {outages.map((outage) => (
          <Circle
            key={outage.id}
            center={[outage.latitude, outage.longitude]}
            radius={outage.radius_km * 1000}
            pathOptions={{ 
              color: 'var(--color-accent)', 
              fillColor: 'var(--color-accent)', 
              fillOpacity: 0.2,
              weight: 2 
            }}
            eventHandlers={{
              mouseover: (e) => {
                if (pinnedId !== outage.id) {
                  e.target.openPopup();
                }
              },
              mouseout: (e) => {
                if (pinnedId !== outage.id) {
                  e.target.closePopup();
                }
              },
              click: (e) => {
                if (pinnedId === outage.id) {
                  setPinnedId(null);
                  e.target.closePopup();
                } else {
                  setPinnedId(outage.id);
                  e.target.openPopup();
                }
              },
              popupclose: () => {
                if (pinnedId === outage.id) {
                  setPinnedId(null);
                }
              }
            }}
          >
            <Popup>
              <div className="font-sans p-1">
                <h3 className="font-bold text-sm mb-1">Outage Details</h3>
                <p className="text-xs text-text-muted mb-2">Radius: {outage.radius_km.toFixed(1)} km</p>
                <div className="flex gap-2 text-xs mb-1">
                  <span className="font-bold">Started:</span>
                  <span>{new Date(outage.created_at).toLocaleString()}</span>
                </div>
                {outage.duration_hours && (
                  <div className="flex gap-2 text-xs mb-1">
                    <span className="font-bold">Duration:</span>
                    <span>{outage.duration_hours} hours</span>
                  </div>
                )}
                {outage.description && (
                  <div className="flex flex-col text-xs mt-2 border-t border-border pt-1">
                    <span className="font-bold">Description:</span>
                    <span className="italic text-text-muted break-words">{outage.description}</span>
                  </div>
                )}
              </div>
            </Popup>
          </Circle>
        ))}

        {selectedLocation && radiusKm && (
          <Circle
            center={[selectedLocation.lat, selectedLocation.lng]}
            radius={radiusKm * 1000}
            pathOptions={{ 
              color: 'var(--color-accent)', 
              fillColor: 'var(--color-accent)', 
              fillOpacity: 0.4,
              weight: 3,
              dashArray: '5, 5'
            }}
          />
        )}
      </MapContainer>
    </div>
  );
}
