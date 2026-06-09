# Development image for the Nuxt 4 frontend.
# Production build/deploy is handled separately (see docs/architecture.md).
FROM node:22-alpine

WORKDIR /app

# Install dependencies first to leverage Docker layer caching.
# node_modules is mounted as a container-managed volume in Compose (B-03),
# so this layer primarily serves standalone `docker build` / first boot.
COPY package.json package-lock.json* ./
RUN npm install

COPY . .

EXPOSE 3000

# Nuxt dev server with HMR; bind to all interfaces so the host can reach it.
ENV HOST=0.0.0.0
ENV PORT=3000
CMD ["npm", "run", "dev"]
