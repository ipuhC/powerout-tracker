import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { OutageMap } from '@/components/map/OutageMap';
import { useOutageStore } from '@/store/useOutageStore';
import { useAuthStore } from '@/store/useAuthStore';
import { Bounds } from '@/types';

const HOW_IT_WORKS = [
  {
    step: '01',
    title: 'Locate Your Area',
    description:
      'Navigate the live map to identify your neighbourhood or area of interest. Outage circles update every 30 seconds without a page reload.',
  },
  {
    step: '02',
    title: 'Report an Outage',
    description:
      'Register a free account, click your location on the map, set the affected radius (0.5–25 km) and the estimated duration — or keep it open until power returns.',
  },
  {
    step: '03',
    title: 'Track & Resolve',
    description:
      'Monitor your active reports, edit details as the situation evolves, and mark them as resolved the moment electricity comes back.',
  },
];

export function LandingPage() {
  const { outages, fetchOutages, isLoading } = useOutageStore();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const [bounds, setBounds] = useState<Bounds | null>(null);

  useEffect(() => {
    if (!bounds) return;
    fetchOutages(bounds);
    const id = setInterval(() => fetchOutages(bounds), 30_000);
    return () => clearInterval(id);
  }, [bounds, fetchOutages]);

  return (
    <div className="w-full overflow-y-auto">
      {/* ── Hero ─────────────────────────────────────────────────────── */}
      <section className="relative bg-grid-pattern border-b border-border/50">
        <div className="max-w-5xl mx-auto px-6 py-20 md:py-28 flex flex-col items-center text-center gap-6">
          {/* Live badge */}
          <div className="inline-flex items-center gap-2 font-mono text-xs uppercase tracking-widest text-text-muted bg-surface border border-border px-4 py-1.5 rounded-full">
            <span className="w-2 h-2 rounded-full bg-accent animate-pulse shadow-[0_0_8px_var(--color-accent)]" />
            Live Community Feed
          </div>

          {/* Two-tone headline */}
          <h1 className="text-5xl md:text-7xl font-bold leading-tight tracking-tight">
            <span className="text-text block">Power Outage</span>
            <span className="text-text-muted block">Reporting Platform</span>
          </h1>

          {/* One-line subtitle */}
          <p className="text-lg md:text-xl text-text-muted max-w-2xl leading-relaxed">
            Report blackouts, track affected areas in real time and help your
            city recover faster — all on a single interactive map.
          </p>

          {/* CTA */}
          <div className="flex flex-wrap gap-4 justify-center mt-2">
            {isAuthenticated ? (
              <Link
                to="/report"
                id="cta-report-outage"
                className="inline-flex items-center gap-2 bg-accent hover:bg-accent/90 text-white font-semibold px-8 py-3 rounded-lg transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5"
              >
                ⚡ Report an Outage
              </Link>
            ) : (
              <>
                <Link
                  to="/register"
                  id="cta-register"
                  className="inline-flex items-center gap-2 bg-accent hover:bg-accent/90 text-white font-semibold px-8 py-3 rounded-lg transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5"
                >
                  Get Started — it's free
                </Link>
                <Link
                  to="/login"
                  id="cta-login"
                  className="inline-flex items-center gap-2 border border-border hover:border-accent/50 text-text font-semibold px-8 py-3 rounded-lg transition-all hover:-translate-y-0.5"
                >
                  Log In
                </Link>
              </>
            )}
          </div>

          {/* Stats strip */}
          <div className="flex items-center gap-6 mt-4 font-mono text-xs text-text-muted uppercase tracking-widest">
            <span>
              <span className="text-accent font-bold text-sm">
                {isLoading ? '…' : outages.length}
              </span>{' '}
              active outages in view
            </span>
            <span className="w-px h-4 bg-border" />
            <span>Refreshes every 30 s</span>
            <span className="w-px h-4 bg-border" />
            <span>No API key required</span>
          </div>
        </div>
      </section>

      {/* ── How it Works ─────────────────────────────────────────────── */}
      <section className="max-w-5xl mx-auto px-6 py-16 md:py-20">
        {/* Section label */}
        <p className="font-mono text-xs uppercase tracking-widest text-text-muted mb-8">
          How it works
        </p>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {HOW_IT_WORKS.map(({ step, title, description }) => (
            <div
              key={step}
              className="bg-surface border border-border rounded-lg p-6 flex flex-col gap-3 hover:border-accent/40 transition-colors"
            >
              {/* Step number */}
              <span className="font-mono font-bold text-accent text-sm">
                {step}
              </span>
              {/* Title */}
              <h3 className="font-bold text-text text-lg leading-snug">
                {title}
              </h3>
              {/* Description */}
              <p className="text-sm text-text-muted leading-relaxed">
                {description}
              </p>
            </div>
          ))}
        </div>
      </section>

      {/* ── Public Outage Map ─────────────────────────────────────────── */}
      <section className="max-w-5xl mx-auto px-6 pb-20">
        {/* Window-style panel header */}
        <div className="flex items-center gap-2 bg-surface border border-border border-b-0 rounded-t-lg px-4 py-3">
          <span className="w-3 h-3 rounded-full bg-accent" />
          <span className="w-3 h-3 rounded-full bg-border" />
          <span className="w-3 h-3 rounded-full bg-border" />
          <span className="font-mono text-xs text-text-muted ml-3 uppercase tracking-widest">
            live-outage-map.view
          </span>
          {isLoading && (
            <span className="ml-auto font-mono text-xs text-text-muted flex items-center gap-1.5">
              <span className="w-2 h-2 border border-text-muted border-t-transparent rounded-full animate-spin inline-block" />
              Fetching…
            </span>
          )}
          {!isLoading && (
            <span className="ml-auto font-mono text-xs text-accent font-bold flex items-center gap-1.5">
              <span className="w-2 h-2 rounded-full bg-accent animate-pulse" />
              {outages.length} active
            </span>
          )}
        </div>

        {/* Map container */}
        <div className="h-[55vh] border border-border rounded-b-lg overflow-hidden shadow-lg">
          <OutageMap
            center={[10.202, -68.0077]}
            zoom={12}
            outages={outages}
            interactive={true}
            onBoundsChange={setBounds}
          />
        </div>

        {/* Map caption */}
        <p className="mt-3 font-mono text-xs text-text-muted text-center">
          Click any circle to view outage details · Data filtered by visible
          bounding box · No personal data is shown
        </p>
      </section>
    </div>
  );
}
