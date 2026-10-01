export interface PointLocation {
  type: 'Point';
  coordinates: [number, number]; // [longitude, latitude]
}

export interface Outage {
  id: number;
  radius_km: number;
  latitude: number;
  longitude: number;
  status: 'active' | 'closed' | 'expired';
  description?: string;
  remaining_seconds?: number;
  duration_hours?: number; // From user's private reports
  created_at: string;
  expires_at?: string;
}

export interface Bounds {
  min_lat: number;
  min_lng: number;
  max_lat: number;
  max_lng: number;
}
