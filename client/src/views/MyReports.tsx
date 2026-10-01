import { useEffect, useState } from 'react';
import { apiClient } from '@/services/apiClient';
import { Outage } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
export function MyReports() {
  const [reports, setReports] = useState<Outage[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState('');
  const [editingReport, setEditingReport] = useState<Outage | null>(null);
  
  // Edit Form State
  const [editRadius, setEditRadius] = useState<number>(5);
  const [editDurationType, setEditDurationType] = useState<'fixed' | 'until_closed'>('fixed');
  const [editDurationHours, setEditDurationHours] = useState<number | ''>('');
  const [editDescription, setEditDescription] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [editError, setEditError] = useState('');

  const fetchReports = async () => {
    setIsLoading(true);
    try {
      const response = await apiClient.get('/reports');
      const reportsArray = response.data?.data?.reports || response.data?.data || [];
      setReports(reportsArray);
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to fetch reports');
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchReports();
  }, []);

  const handleResolve = async (id: number) => {
    try {
      await apiClient.post(`/reports/${id}/close`);
      fetchReports();
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to close report');
    }
  };

  const handleEditClick = (report: Outage) => {
    setEditingReport(report);
    setEditRadius(report.radius_km);
    setEditDurationType(report.duration_hours ? 'fixed' : 'until_closed');
    setEditDurationHours(report.duration_hours || '');
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    setEditDescription((report as any).description || '');
    setEditError('');
  };

  const handleEditSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingReport) return;
    
    setIsSubmitting(true);
    setEditError('');
    
    try {
      await apiClient.patch(`/reports/${editingReport.id}`, {
        radius_km: editRadius,
        duration_type: editDurationType,
        duration_hours: editDurationType === 'fixed' ? editDurationHours : null,
        description: editDescription || undefined
      });
      setEditingReport(null);
      fetchReports();
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    } catch (err: any) {
      setEditError(err.response?.data?.message || 'Failed to update report');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (isLoading) return <div className="p-8 flex justify-center"><div className="animate-spin h-6 w-6 border-2 border-accent border-t-transparent rounded-full"></div></div>;
  if (error) return <div className="p-8 text-accent font-bold bg-accent/10 border border-accent/20 rounded-lg">{error}</div>;

  return (
    <div className="p-4 md:p-8 max-w-4xl mx-auto w-full h-full overflow-y-auto">
      <div className="flex justify-between items-center mb-6">
        <h2 className="text-3xl font-bold">My Reports</h2>
        <Button variant="outline" onClick={fetchReports} className="bg-surface/50 border-border/50">Refresh</Button>
      </div>

      {reports.length === 0 ? (
        <Card className="glass border-border/50 text-center p-12">
          <p className="text-text-muted mb-4">You haven't reported any outages yet.</p>
        </Card>
      ) : (
        <div className="space-y-4">
          {reports.map((report) => (
            <Card key={report.id} className="glass border-border/50 hover:border-accent/50 transition-colors">
              <CardContent className="p-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                <div className="space-y-2">
                  <div className="flex items-center gap-3">
                    <Badge variant={report.status === 'active' ? 'default' : 'secondary'} className={
                      report.status === 'active' ? 'bg-accent hover:bg-accent/80' : 
                      report.status === 'closed' ? 'bg-green-500/10 text-green-500 border-green-500/20' : ''
                    }>
                      {report.status.toUpperCase()}
                    </Badge>
                    <span className="text-xs text-text-muted font-mono bg-surface/50 px-2 py-1 rounded-md">
                      {new Date(report.created_at).toLocaleString()}
                    </span>
                  </div>
                  <div className="text-sm">
                    <span className="font-semibold text-text">Radius:</span> <span className="text-text-muted">{report.radius_km.toFixed(1)} km</span>
                  </div>
                  {report.duration_hours && (
                    <div className="text-sm">
                      <span className="font-semibold text-text">Expected duration:</span> <span className="text-text-muted">{report.duration_hours}h</span>
                    </div>
                  )}
                  {/* eslint-disable-next-line @typescript-eslint/no-explicit-any */}
                  {(report as any).description && (
                    <div className="mt-3 text-sm text-text-muted italic border-l-2 border-accent/50 pl-3 py-1 bg-surface/30 rounded-r-md">
                      {/* eslint-disable-next-line @typescript-eslint/no-explicit-any */}
                      "{(report as any).description}"
                    </div>
                  )}
                </div>

                {report.status === 'active' && (
                  <div className="flex gap-3 mt-4 md:mt-0">
                    <Button variant="secondary" onClick={() => handleEditClick(report)} className="bg-surface/80 hover:bg-surface">
                      Edit
                    </Button>
                    <Button onClick={() => handleResolve(report.id)} className="bg-accent hover:bg-accent/90 text-white">
                      Power is Back
                    </Button>
                  </div>
                )}
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {/* Edit Modal */}
      <Dialog open={!!editingReport} onOpenChange={(open: boolean) => {
        if (!open) {
          setEditingReport(null);
          setEditError('');
        }
      }}>
        <DialogContent className="sm:max-w-[425px] bg-background border-border">
          <DialogHeader>
            <DialogTitle>Edit Report</DialogTitle>
            <DialogDescription>
              Make changes to your active report here. Click save when you're done.
            </DialogDescription>
          </DialogHeader>
          
          {editError && (
            <div className="bg-accent/10 border border-accent/20 text-accent p-3 rounded-lg text-sm">
              {editError}
            </div>
          )}

          <form onSubmit={handleEditSubmit} className="space-y-6 py-4">
            <div className="space-y-3">
              <Label>Affected Radius: {editRadius} km</Label>
              <input 
                type="range" 
                min="0.5" 
                max="25" 
                step="0.5" 
                value={editRadius} 
                onChange={(e) => setEditRadius(parseFloat(e.target.value))}
                className="w-full accent-accent"
              />
            </div>

            <div className="space-y-3">
              <Label>Duration</Label>
              <div className="flex gap-6 mb-3">
                <label className="flex items-center gap-2 text-sm cursor-pointer">
                  <input 
                    type="radio" 
                    checked={editDurationType === 'fixed'} 
                    onChange={() => setEditDurationType('fixed')}
                    className="accent-accent w-4 h-4"
                  />
                  Fixed Time
                </label>
                <label className="flex items-center gap-2 text-sm cursor-pointer">
                  <input 
                    type="radio" 
                    checked={editDurationType === 'until_closed'} 
                    onChange={() => setEditDurationType('until_closed')}
                    className="accent-accent w-4 h-4"
                  />
                  Until I close it
                </label>
              </div>
              
              {editDurationType === 'fixed' && (
                <div className="flex items-center gap-3 mt-4">
                  <Input 
                    type="number" 
                    min="1" 
                    max="72" 
                    value={editDurationHours}
                    onChange={(e) => setEditDurationHours(parseInt(e.target.value))}
                    className="w-24 bg-surface/50 focus-visible:ring-accent"
                    required
                  />
                  <span className="text-sm text-text-muted">hours</span>
                </div>
              )}
            </div>

            <div className="space-y-3">
              <Label>Description</Label>
              <textarea 
                value={editDescription}
                onChange={(e) => setEditDescription(e.target.value)}
                className="flex min-h-[100px] w-full rounded-md border border-input bg-surface/50 px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 focus-visible:ring-accent transition-colors"
                placeholder="Update description..."
              />
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setEditingReport(null)}>
                Cancel
              </Button>
              <Button type="submit" disabled={isSubmitting} className="bg-accent hover:bg-accent/90 text-white">
                {isSubmitting ? 'Saving...' : 'Save changes'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
