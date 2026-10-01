import { Routes, Route } from 'react-router-dom';
import { AppLayout } from '@/components/layout/AppLayout';
import { ProtectedRoute } from '@/components/auth/ProtectedRoute';
import { PublicMap } from '@/views/PublicMap';
import { ReportCreation } from '@/views/ReportCreation';
import { MyReports } from '@/views/MyReports';
import { Login } from '@/views/Login';
import { Register } from '@/views/Register';

function App() {
  return (
    <Routes>
      <Route element={<AppLayout />}>
        {/* Public Routes */}
        <Route path="/" element={<PublicMap />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />

        {/* Protected Routes */}
        <Route 
          path="/report" 
          element={
            <ProtectedRoute>
              <ReportCreation />
            </ProtectedRoute>
          } 
        />
        <Route 
          path="/my-reports" 
          element={
            <ProtectedRoute>
              <MyReports />
            </ProtectedRoute>
          } 
        />
      </Route>
    </Routes>
  );
}

export default App
