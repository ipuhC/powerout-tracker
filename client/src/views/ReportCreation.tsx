import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { OutageMap } from '@/components/map/OutageMap';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { apiClient } from '@/services/apiClient';

export function ReportCreation() {
  const navigate = useNavigate();
  const [location, setLocation] = useState<{ lat: number; lng: number } | null>(null);
  const [radiusKm, setRadiusKm] = useState<number>(5);
  const [durationType, setDurationType] = useState<'fixed' | 'until_closed'>('fixed');
  const [durationHours, setDurationHours] = useState<number>(4);
  const [description, setDescription] = useState('');
  const [error, setError] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!location) {
      setError('Please select a location on the map');
      return;
    }

    setIsSubmitting(true);
    setError('');

    try {
      await apiClient.post('/reports', {
        latitude: location.lat,
        longitude: location.lng,
        radius_km: radiusKm,
        duration_type: durationType,
        duration_hours: durationType === 'fixed' ? durationHours : null,
        description: description || undefined
      });
      navigate('/my-reports');
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    } catch (err: any) {
      const errData = err.response?.data?.error;
      if (errData) {
        const details = errData.details ? ' ' + Object.values(errData.details).flat().join(' ') : '';
        setError(errData.message + details);
      } else {
        setError(err.response?.data?.message || 'Failed to submit report');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="flex flex-col md:flex-row h-[calc(100vh-4rem)]">
      <div className="w-full md:w-1/3 p-6 md:p-8 overflow-y-auto border-r border-border/50 glass">
        <h2 className="text-2xl font-bold mb-6 text-text">Report Outage</h2>
        
        {error && (
          <div className="bg-accent/10 border border-accent/20 text-accent p-3 rounded-lg mb-6 text-sm">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-8">
          <div className="space-y-3">
            <Label className="text-base font-semibold">1. Location</Label>
            <p className="text-sm text-text-muted">Click on the map to set the center of the outage.</p>
            {location ? (
              <div className="font-mono text-xs p-3 bg-surface/50 border border-border/50 rounded-lg">
                Lat: {location.lat.toFixed(4)}, Lng: {location.lng.toFixed(4)}
              </div>
            ) : (
              <div className="text-accent text-sm font-semibold animate-pulse">Waiting for selection...</div>
            )}
          </div>

          <div className="space-y-3">
            <Label className="text-base font-semibold">
              2. Affected Radius: {radiusKm} km
            </Label>
            <input 
              type="range" 
              min="0.5" 
              max="25" 
              step="0.5" 
              value={radiusKm} 
              onChange={(e) => setRadiusKm(parseFloat(e.target.value))}
              className="w-full accent-accent"
            />
          </div>

          <div className="space-y-3">
            <Label className="text-base font-semibold">3. Duration</Label>
            <div className="flex gap-6 mb-3">
              <label className="flex items-center gap-2 text-sm cursor-pointer">
                <input 
                  type="radio" 
                  checked={durationType === 'fixed'} 
                  onChange={() => setDurationType('fixed')}
                  className="accent-accent w-4 h-4"
                />
                Fixed Time
              </label>
              <label className="flex items-center gap-2 text-sm cursor-pointer">
                <input 
                  type="radio" 
                  checked={durationType === 'until_closed'} 
                  onChange={() => setDurationType('until_closed')}
                  className="accent-accent w-4 h-4"
                />
                Until I close it
              </label>
            </div>
            
            {durationType === 'fixed' && (
              <div className="flex items-center gap-3 mt-4">
                <Input 
                  type="number" 
                  min="1" 
                  max="72" 
                  value={durationHours}
                  onChange={(e) => setDurationHours(parseInt(e.target.value))}
                  className="w-24 bg-surface/50 focus-visible:ring-accent"
                />
                <span className="text-sm text-text-muted">hours</span>
              </div>
            )}
          </div>

          <div className="space-y-3">
            <Label className="text-base font-semibold">4. Description (Optional)</Label>
            <textarea 
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              className="flex min-h-[100px] w-full rounded-md border border-input bg-surface/50 px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 focus-visible:ring-accent transition-colors"
              placeholder="E.g. Transformer blew up on Main St."
            />
          </div>

          <Button type="submit" className="w-full py-6 text-base bg-accent hover:bg-accent/90 text-white" disabled={isSubmitting || !location}>
            {isSubmitting ? 'Submitting...' : 'Submit Report'}
          </Button>
        </form>
      </div>

      <div className="w-full md:w-2/3 h-full relative cursor-crosshair">
        <OutageMap 
          center={[10.2020, -68.0077]}
          zoom={12}
          interactive={true}
          selectedLocation={location}
          radiusKm={radiusKm}
          onLocationSelect={setLocation}
        />
      </div>
    </div>
  );
}
