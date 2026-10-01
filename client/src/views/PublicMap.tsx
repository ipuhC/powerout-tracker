import { useEffect, useRef, useState } from 'react';
import { OutageMap } from '@/components/map/OutageMap';
import { useOutageStore } from '@/store/useOutageStore';
import { Bounds, Outage } from '@/types';

export function PublicMap() {
  const { outages, fetchOutages, isLoading } = useOutageStore();
  const [bounds, setBounds] = useState<Bounds | null>(null);

  // Highlighted row/circle state
  const [highlightedId, setHighlightedId] = useState<number | null>(null);
  const [flyToId, setFlyToId] = useState<number | null>(null);

  // Ref to scroll into view when a table row is clicked
  const mapContainerRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!bounds) return;

    fetchOutages(bounds);

    const intervalId = setInterval(() => {
      fetchOutages(bounds);
    }, 30000); // Poll every 30s

    return () => clearInterval(intervalId);
  }, [bounds, fetchOutages]);

  // Sort a copy of outages: most recent first
  const sortedOutages: Outage[] = [...outages].sort(
    (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
  );

  const handleRowClick = (outage: Outage) => {
    setHighlightedId(outage.id);

    // Always re-trigger flyTo even if same id (reset then set)
    setFlyToId(null);
    requestAnimationFrame(() => setFlyToId(outage.id));

    // Scroll the map into view smoothly
    mapContainerRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  return (
    <div className="w-full flex flex-col items-center p-4 md:p-8 overflow-y-auto">

      {/* Map Section */}
      <div
        ref={mapContainerRef}
        className="w-full md:w-[85%] h-[50vh] md:h-[70vh] rounded-[24px] overflow-hidden border border-border/50 shadow-xl relative shrink-0 transition-all duration-300 hover:shadow-2xl"
      >
        <OutageMap
          center={[10.2020, -68.0077]}
          zoom={12}
          outages={outages}
          interactive={true}
          onBoundsChange={setBounds}
          highlightedId={highlightedId}
          flyToId={flyToId}
        />

        {/* Overlay Stats */}
        <div className="absolute bottom-6 left-6 z-[400] glass p-4 rounded-[16px] pointer-events-none transition-all duration-300">
          {isLoading ? (
            <p className="text-sm font-mono text-text-muted flex items-center gap-2">
              <span className="w-2 h-2 rounded-full border border-text-muted border-t-transparent animate-spin"></span>
              Fetching live data...
            </p>
          ) : (
            <p className="text-sm font-mono text-accent font-bold flex items-center gap-2 text-lg">
              <span className="w-3 h-3 rounded-full bg-accent animate-pulse shadow-[0_0_10px_var(--color-accent)]"></span>
              {outages.length} active outages
            </p>
          )}
        </div>
      </div>

      {/* Table Section */}
      <div className="w-full md:w-[85%] mt-12 mb-12">
        <h2 className="text-2xl font-bold mb-6 flex items-center gap-3">
          <span className="bg-accent/10 text-accent p-2 rounded-lg">📊</span>
          Latest Active Reports in View
        </h2>

        {sortedOutages.length === 0 ? (
          <div className="p-12 text-center text-text-muted border border-border/50 rounded-[20px] bg-surface/50 border-dashed">
            <span className="text-4xl mb-4 block opacity-50">✨</span>
            <p className="text-lg">No active outages reported in this area.</p>
            <p className="text-sm opacity-70">Everything is running smoothly.</p>
          </div>
        ) : (
          <div className="overflow-x-auto border border-border/50 rounded-[20px] bg-surface/30 backdrop-blur-sm shadow-sm">
            <table className="w-full text-left text-sm whitespace-nowrap">
              <thead className="bg-surface/80 border-b border-border/50 font-mono text-text-muted uppercase text-xs tracking-wider">
                <tr>
                  <th className="px-4 py-3 font-medium">Started</th>
                  <th className="px-4 py-3 font-medium">Radius</th>
                  <th className="px-4 py-3 font-medium">Location</th>
                  <th className="px-4 py-3 font-medium w-full">Description</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {sortedOutages.slice(0, 10).map((outage) => {
                  const isHighlighted = outage.id === highlightedId;
                  return (
                    <tr
                      key={outage.id}
                      onClick={() => handleRowClick(outage)}
                      className={[
                        'cursor-pointer transition-colors duration-150',
                        isHighlighted
                          ? 'bg-accent/10 border-l-2 border-accent'
                          : 'hover:bg-accent/5',
                      ].join(' ')}
                      title="Click to focus on map"
                    >
                      <td className="px-4 py-3 font-mono text-xs">
                        {new Date(outage.created_at).toLocaleString()}
                      </td>
                      <td className="px-4 py-3 font-mono">
                        {outage.radius_km.toFixed(1)} km
                      </td>
                      <td className="px-4 py-3 font-mono text-text-muted text-xs">
                        {outage.latitude.toFixed(4)}, {outage.longitude.toFixed(4)}
                      </td>
                      <td className="px-4 py-3 truncate max-w-xs text-text-muted">
                        {outage.description || (
                          <span className="opacity-40 italic">No description</span>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
