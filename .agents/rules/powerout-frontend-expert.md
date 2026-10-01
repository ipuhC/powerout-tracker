name: powerout-frontend-expert
description: Use when creating React/TypeScript components, pages, or features for the Power Outage Tracker. Enforces Tailwind CSS (Design Tokens only), Zustand, Axios, Leaflet encapsulation, and technical-minimalist UX patterns.
---

# Power Outage Tracker - Frontend Expert

Modern React/TypeScript development patterns strictly tailored for the Senior Technical Test requirements.

## 🎯 Core Architecture Overview

- **Design Token Driven** - Strict adherence to Tailwind CSS mapped to required CSS variables. No arbitrary colors.
- **Service Layer Architecture** - Axios with Sanctum interceptors. Zero raw fetch calls in components.
- **State Management** - Zustand for global state. Local state only for pure UI toggles.
- **Encapsulated 3rd Parties** - Leaflet map logic strictly isolated in adapter components.
- **Type-safe patterns** - Strict TypeScript, no `any` types.

---

## 🚫 Critical Hard Rules

### 1. Tailwind & Design Tokens ONLY
Do not use raw hex colors or arbitrary values. You MUST use the mapped tailwind classes.
```tsx
// ❌ NEVER - Arbitrary colors or inline styles
<div className="bg-[#121212] text-[#ff3333]">

// ✅ ALWAYS - Mapped Tailwind config
<div className="bg-bg text-accent border-grid">
```

### 2. Map Encapsulation (Leaflet Anti-Leak)
The application views must not know about Leaflet instances, markers, or popups.
```tsx
// ❌ NEVER - Leaking Leaflet logic to the view
import { MapContainer, Marker } from 'react-leaflet';

// ✅ ALWAYS - Encapsulated Adapter
import { OutageMap } from '@/components/map/OutageMap';
```

### 3. API & UI States Separation
Every component that fetches data MUST handle 3 distinct visual states: Loading, Empty, and Error (especially HTTP 422 mapping).
```tsx
// ❌ NEVER - Assuming data always exists
return <div>{outages.map(o => <Card />)}</div>

// ✅ ALWAYS - Handled correctly
if (isLoading) return <Spinner />;
if (error) return <ErrorAlert message={error.message} />;
if (!outages.length) return <EmptyState />;
return <div>{outages.map(o => <Card />)}</div>
```

---

## 🧠 UX & Interaction Intelligence (Technical Minimalist)

While constrained to specific Design Tokens, apply these Senior-level UX practices:

- **Micro-interactions:** All clickable elements must have deliberate `hover:`, `focus-visible:`, and `disabled:` states (using `--color-accent-soft` or opacity).
- **Two-Tone Typography:** Large headings must use the pattern: line 1 in `text-text`, line 2 in `text-text-muted`.
- **Window-Style Panels:** Wrap map or complex data components in a "window" frame: light-gray header, 3 small dots (first in `color-accent`), and a monospace caption.
- **Flat Design:** Use generous padding, thin `border-border`, and **no heavy drop shadows**.
- **Data Display:** Use **monospace font** for step numbers, timestamps, coordinates, and section labels (with wide tracking).
- **Graceful Degradation:** The UI must be fully responsive, switching complex desktop layouts (side panels) to mobile-friendly patterns (bottom sheets/drawers) gracefully.

---

## 📂 File Organization

- `src/assets/`: Global `index.css` with CSS variables.
- `src/components/ui/`: Reusable, dumb UI components (Buttons, Cards, Inputs).
- `src/components/map/`: The Leaflet encapsulation layer (`OutageMap.tsx`).
- `src/views/`: Domain-specific pages (`DashboardView`, `LoginView`).
- `src/services/`: Axios instances and API repositories (`apiClient.ts`).
- `src/store/`: Zustand stores (`useOutageStore.ts`).
