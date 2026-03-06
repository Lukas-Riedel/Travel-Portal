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
RUN chown -R nginx:nginx /usr/share/nginx/html && \
    chown -R nginx:nginx /var/cache/nginx && \
    chown -R nginx:nginx /var/log/nginx && \
    chown -R nginx:nginx /etc/nginx/conf.d
RUN touch /var/run/nginx.pid && \
    chown -R nginx:nginx /var/run/nginx.pid
USER 101
EXPOSE 8080
CMD ["nginx", "-g", "daemon off;"]