# -----------------------------------------------------------------------------
# Stage 1: Build the React application
# -----------------------------------------------------------------------------
FROM node:20-alpine AS build

WORKDIR /app

# Build arguments for Vite environment variables
ARG VITE_API_BASE_URL=/api/v1
ENV VITE_API_BASE_URL=$VITE_API_BASE_URL

# Copy package manifests and install dependencies
COPY package*.json ./
RUN npm ci || npm install

# Copy application source code
COPY . .

# Compile TypeScript and build production bundle
RUN npm run build

# -----------------------------------------------------------------------------
# Stage 2: Serve production assets via NGINX
# -----------------------------------------------------------------------------
FROM nginx:alpine

# Copy SPA Nginx configuration
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copy compiled assets from build stage
COPY --from=build /app/dist /usr/share/nginx/html

# Expose HTTP port
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
