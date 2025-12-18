FROM node:18-alpine AS build-stage
WORKDIR /app
COPY Portal/package*.json ./
RUN npm install --legacy-peer-deps
COPY Portal/ ./
RUN npm run build
FROM nginx:stable-alpine
RUN rm /etc/nginx/conf.d/default.conf
COPY Portal/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build-stage /app/dist /usr/share/nginx/html
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]