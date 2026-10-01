export interface PointLocation {
  type: 'Point';
  coordinates: [number, number]; // [longitude, latitude]
}

export interface Outage {
  id: number;
  radius_meters: number;
  starts_at: string;
  duration_hours: number | null;
  status: 'active' | 'resolved' | 'expired';
  location: PointLocation;
  created_at: string;
}

export interface Bounds {
  min_lat: number;
  min_lng: number;
  max_lat: number;
  max_lng: number;
}
