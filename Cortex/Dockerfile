FROM python:3.11-slim as builder
WORKDIR /app
COPY Cortex/requirements.txt ./
RUN pip install --no-cache-dir --prefix=/install -r requirements.txt
FROM python:3.11-slim
WORKDIR /app
COPY --from=builder /install /usr/local
COPY Cortex/. ./
ENV PYTHONPATH=/app \
    PYTHONUNBUFFERED=1 \
    PYTHONDONTWRITEBYTECODE=1
EXPOSE 8080
CMD ["python", "main.py"]