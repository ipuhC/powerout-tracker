import React from 'react';
import { cn } from '@/utils/cn';

interface WindowPanelProps extends React.HTMLAttributes<HTMLDivElement> {
  title?: string;
  children: React.ReactNode;
}

export const WindowPanel: React.FC<WindowPanelProps> = ({ title, children, className, ...props }) => {
  return (
    <div 
      className={cn("bg-surface border border-border shadow-sm rounded-md overflow-hidden flex flex-col", className)}
      {...props}
    >
      {/* Header bar */}
      <div className="bg-bg border-b border-border px-3 py-2 flex items-center gap-4">
        {/* Traffic light buttons */}
        <div className="flex gap-1.5">
          <div className="w-2.5 h-2.5 rounded-full bg-[#FF5F56] border border-[#E0443E]"></div>
          <div className="w-2.5 h-2.5 rounded-full bg-[#FFBD2E] border border-[#DEA123]"></div>
          <div className="w-2.5 h-2.5 rounded-full bg-[#27C93F] border border-[#1AAB29]"></div>
        </div>
        {title && (
          <div className="flex-1 text-center font-mono text-xs text-text-muted select-none">
            {title}
          </div>
        )}
        {title && <div className="w-[42px]" />} {/* Spacer to balance title centering */}
      </div>
      
      {/* Content */}
      <div className="flex-1 flex flex-col relative bg-surface p-4">
        {children}
      </div>
    </div>
  );
};
