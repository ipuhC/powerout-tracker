import { Routes, Route } from 'react-router-dom';
import { AppLayout } from '@/components/layout/AppLayout';
import { ProtectedRoute } from '@/components/auth/ProtectedRoute';

function App() {
  return (
    <Routes>
      <Route element={<AppLayout />}>
        {/* Public Routes */}
        <Route path="/" element={<div className="p-8 flex items-center justify-center h-full"><h1 className="text-2xl font-mono text-accent">Map Integration Pending (Phase 5)</h1></div>} />
        <Route path="/login" element={<div className="p-8">Login View Placeholder</div>} />
        <Route path="/register" element={<div className="p-8">Register View Placeholder</div>} />

        {/* Protected Routes */}
        <Route 
          path="/my-reports" 
          element={
            <ProtectedRoute>
              <div className="p-8">My Reports View Placeholder</div>
            </ProtectedRoute>
          } 
        />
      </Route>
    </Routes>
  );
}

export default App
