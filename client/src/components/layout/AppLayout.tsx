import React, { useEffect } from 'react';
import { Outlet, Link } from 'react-router-dom';
import { useAuthStore } from '@/store/useAuthStore';
import { Button } from '@/components/ui/button';

export const AppLayout: React.FC = () => {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
  const user = useAuthStore((state) => state.user);
  const logout = useAuthStore((state) => state.clearAuth);
  const checkAuth = useAuthStore((state) => state.checkAuth);

  useEffect(() => {
    checkAuth();
  }, [checkAuth]);

  const [isDarkMode, setIsDarkMode] = React.useState(false);
  const [showHelpModal, setShowHelpModal] = React.useState(false);
  const [showDropdown, setShowDropdown] = React.useState(false);

  useEffect(() => {
    // Check local storage or system preference
    if (localStorage.getItem('theme') === 'dark' || 
        (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      setIsDarkMode(true);
      document.documentElement.classList.add('dark');
    }
  }, []);

  const toggleDarkMode = () => {
    if (isDarkMode) {
      document.documentElement.classList.remove('dark');
      localStorage.setItem('theme', 'light');
    } else {
      document.documentElement.classList.add('dark');
      localStorage.setItem('theme', 'dark');
    }
    setIsDarkMode(!isDarkMode);
  };

  return (
    <div className="flex h-screen bg-bg text-text font-sans overflow-hidden">
      {/* Help Modal */}
      {showHelpModal && (
        <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 transition-all duration-300">
          <div className="glass rounded-[20px] p-8 max-w-md w-full relative transform scale-100 animate-in fade-in zoom-in-95 duration-200">
            <button 
              onClick={() => setShowHelpModal(false)}
              className="absolute top-5 right-5 text-text-muted hover:text-accent transition-colors w-8 h-8 flex items-center justify-center rounded-full bg-surface/50 hover:bg-surface"
            >
              ✕
            </button>
            <h2 className="text-2xl font-bold mb-6 flex items-center gap-3">
              <span className="text-accent text-3xl">💡</span> How to use the app
            </h2>
            <div className="space-y-4">
              <div className="bg-surface/50 border border-border/50 p-5 rounded-radius hover:border-accent/50 transition-colors">
                <div className="text-accent font-mono font-bold mb-1 text-lg">01. Locate</div>
                <p className="text-sm text-text-muted">Navigate the map to find your neighborhood or area of interest.</p>
              </div>
              <div className="bg-surface/50 border border-border/50 p-5 rounded-radius hover:border-accent/50 transition-colors">
                <div className="text-accent font-mono font-bold mb-1 text-lg">02. Report</div>
                <p className="text-sm text-text-muted">Register an account, select an affected area, set a radius and the estimated duration.</p>
              </div>
              <div className="bg-surface/50 border border-border/50 p-5 rounded-radius hover:border-accent/50 transition-colors">
                <div className="text-accent font-mono font-bold mb-1 text-lg">03. Track</div>
                <p className="text-sm text-text-muted">Watch live updates as power is restored by the community.</p>
              </div>
            </div>
            <Button variant="default"  className="w-full mt-8 py-3 text-lg shadow-md hover:shadow-lg transition-all" onClick={() => setShowHelpModal(false)}>Got it!</Button>
          </div>
        </div>
      )}

      {/* Left Sidebar */}
      <aside className="w-64 glass border-r border-border shrink-0 flex flex-col justify-between z-40 hidden md:flex">
        <div className="p-4 flex flex-col gap-6 mt-4">
          <nav className="flex flex-col gap-2">
            <Link to="/" className="flex items-center gap-3 px-4 py-3 rounded-[12px] hover:bg-surface/50 hover:text-accent transition-colors font-medium text-sm">
              <span className="text-xl">🏠</span> Home
            </Link>
            <Link to="/map" className="flex items-center gap-3 px-4 py-3 rounded-[12px] hover:bg-surface/50 hover:text-accent transition-colors font-medium text-sm">
              <span className="text-xl">🗺️</span> Live Map
            </Link>
            
            {isAuthenticated ? (
              <>
                <Link to="/report" className="flex items-center gap-3 px-4 py-3 rounded-[12px] hover:bg-surface/50 hover:text-accent transition-colors font-medium text-sm">
                  <span className="text-xl">⚡</span> Report Outage
                </Link>
                <Link to="/my-reports" className="flex items-center gap-3 px-4 py-3 rounded-[12px] hover:bg-surface/50 hover:text-accent transition-colors font-medium text-sm">
                  <span className="text-xl">📋</span> My Reports
                </Link>
              </>
            ) : (
              <>
                <div className="h-px bg-border/50 my-2 mx-4"></div>
                <Link to="/login" className="flex items-center gap-3 px-4 py-3 rounded-[12px] hover:bg-surface/50 hover:text-accent transition-colors font-medium text-sm">
                  <span className="text-xl">🔑</span> Log in
                </Link>
                <Link to="/register" className="flex items-center gap-3 px-4 py-3 rounded-[12px] hover:bg-surface/50 hover:text-accent transition-colors font-medium text-sm">
                  <span className="text-xl">✨</span> Register
                </Link>
              </>
            )}
          </nav>
        </div>

        <div className="p-4 flex flex-col gap-2 border-t border-border/50 mb-4">
          <button 
            onClick={() => setShowHelpModal(true)}
            className="flex items-center gap-3 px-4 py-3 rounded-[12px] hover:bg-surface/50 transition-colors font-medium text-sm text-left"
          >
            <span className="text-xl">💡</span> How to use
          </button>
          
          <button 
            onClick={toggleDarkMode}
            className="flex items-center gap-3 px-4 py-3 rounded-[12px] hover:bg-surface/50 transition-colors font-medium text-sm text-left"
          >
            <span className="text-xl">{isDarkMode ? '☀️' : '🌙'}</span> 
            {isDarkMode ? 'Light Mode' : 'Dark Mode'}
          </button>
        </div>
      </aside>

      {/* Main Right Area */}
      <div className="flex-1 flex flex-col relative overflow-hidden">
        {/* Navbar */}
        <header className="h-16 glass flex items-center justify-between px-6 shrink-0 sticky top-0 z-50">
          <div className="flex items-center gap-4">
            <Link to="/" className="flex items-center gap-2 hover:opacity-80 transition-opacity">
              <img src="/favicon.svg" alt="Logo" className="w-8 h-8" />
              <h1 className="font-bold tracking-tight text-text text-xl">
                PowerOut<span className="text-text-muted font-normal">Tracker</span>
              </h1>
            </Link>
            <div className="h-6 w-px bg-border/50 mx-2 hidden sm:block"></div>
            <div className="hidden sm:flex items-center gap-2 text-xs font-mono text-text-muted uppercase tracking-wider bg-surface/30 px-3 py-1 rounded-full border border-border/50">
              <div className="w-2 h-2 rounded-full bg-accent animate-pulse shadow-[0_0_8px_var(--color-accent)]"></div>
              Live Feed
            </div>
          </div>

          <nav className="flex items-center gap-4">
            {isAuthenticated && user && (
              <div className="relative">
                <button 
                  onClick={() => setShowDropdown(!showDropdown)}
                  className="w-10 h-10 rounded-full bg-gradient-to-tr from-accent to-pink-500 text-white flex items-center justify-center font-bold text-lg shadow-md hover:shadow-lg transition-all transform hover:scale-105 border-2 border-surface"
                >
                  {user?.name ? user.name.charAt(0).toUpperCase() : 'U'}
                </button>
                
                {showDropdown && (
                  <>
                    <div className="fixed inset-0 z-40" onClick={() => setShowDropdown(false)}></div>
                    <div className="absolute right-0 mt-3 w-56 glass rounded-[16px] py-2 shadow-xl border border-border/50 z-50 animate-in fade-in slide-in-from-top-2 duration-200">
                      <div className="px-5 py-3 border-b border-border/50">
                        <p className="text-sm font-bold text-text truncate">{user.name}</p>
                        <p className="text-xs text-text-muted truncate">{user.email}</p>
                      </div>
                      <div className="p-2">
                        <button 
                          onClick={() => {
                            setShowDropdown(false);
                            logout();
                          }} 
                          className="w-full text-left px-3 py-2 text-sm text-accent font-medium hover:bg-accent/10 rounded-lg transition-colors flex items-center gap-2"
                        >
                          <span className="text-lg">🚪</span> Log out
                        </button>
                      </div>
                    </div>
                  </>
                )}
              </div>
            )}
          </nav>
        </header>

        <main className="flex-1 overflow-y-auto relative bg-bg">
          <Outlet />
        </main>
      </div>
    </div>
  );
};
