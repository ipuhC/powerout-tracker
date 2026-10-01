import React from 'react';
import { cn } from '@/utils/cn';

export interface BadgeProps extends React.HTMLAttributes<HTMLDivElement> {
  variant?: 'default' | 'active' | 'resolved' | 'expired';
}

export const Badge: React.FC<BadgeProps> = ({ className, variant = 'default', children, ...props }) => {
  const variants = {
    default: "bg-surface text-text-muted border border-border",
    active: "bg-accent-soft text-accent border border-accent/20",
    resolved: "bg-[#27C93F]/10 text-[#1AAB29] border border-[#27C93F]/20",
    expired: "bg-surface text-text-muted border border-border opacity-70",
  };

  return (
    <div
      className={cn(
        "inline-flex items-center rounded-sm px-2 py-0.5 text-[10px] font-mono font-semibold uppercase transition-colors focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2",
        variants[variant],
        className
      )}
      {...props}
    >
      {children}
    </div>
  );
};
