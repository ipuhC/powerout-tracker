import React, { useEffect } from 'react';
import { Outlet, Link } from 'react-router-dom';
import { useAuthStore } from '@/store/useAuthStore';
import { Button } from '@/components/ui/Button';

export const AppLayout: React.FC = () => {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
  const user = useAuthStore((state) => state.user);
  const logout = useAuthStore((state) => state.clearAuth);
  const checkAuth = useAuthStore((state) => state.checkAuth);

  useEffect(() => {
    checkAuth();
  }, [checkAuth]);

  return (
    <div className="min-h-screen bg-bg text-text flex flex-col font-sans">
      {/* Navbar */}
      <header className="h-14 border-b border-border bg-surface flex items-center justify-between px-6 shrink-0">
        <div className="flex items-center gap-4">
          <Link to="/" className="flex items-center gap-2 hover:opacity-80 transition-opacity">
            <img src="/favicon.svg" alt="Logo" className="w-6 h-6" />
            <h1 className="font-bold tracking-tight text-text">
              PowerOut<span className="text-text-muted font-normal">Tracker</span>
            </h1>
          </Link>
          <div className="h-4 w-px bg-border mx-2"></div>
          <div className="flex items-center gap-1.5 text-[10px] font-mono text-text-muted uppercase tracking-wider">
            <div className="w-1.5 h-1.5 rounded-full bg-accent animate-pulse"></div>
            Live Feed
          </div>
        </div>

        <nav className="flex items-center gap-4">
          <Link to="/" className="text-sm font-medium hover:text-accent transition-colors">Map</Link>
          
          {isAuthenticated ? (
            <>
              <Link to="/my-reports" className="text-sm font-medium hover:text-accent transition-colors">
                My Reports
              </Link>
              <div className="h-4 w-px bg-border mx-1"></div>
              <span className="text-xs font-mono text-text-muted">{user?.name}</span>
              <Button variant="ghost" className="h-7 px-2 text-xs" onClick={() => logout()}>
                Logout
              </Button>
            </>
          ) : (
            <>
              <Link to="/login">
                <Button variant="ghost" className="h-8 text-sm">Log in</Button>
              </Link>
              <Link to="/register">
                <Button variant="primary" className="h-8 text-sm">Register</Button>
              </Link>
            </>
          )}
        </nav>
      </header>

      {/* Main Content Area */}
      <main className="flex-1 relative overflow-hidden flex flex-col">
        <Outlet />
      </main>
    </div>
  );
};
