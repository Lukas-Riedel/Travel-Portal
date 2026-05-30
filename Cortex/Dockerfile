FROM python:3.11-slim as builder
WORKDIR /app
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    cmake \
    && rm -rf /var/lib/apt/lists/*
COPY Cortex/requirements.txt ./
RUN CMAKE_ARGS="-DLLAMA_NATIVE=on" pip install --no-cache-dir --prefix=/install -r requirements.txt
FROM python:3.11-slim
WORKDIR /app
COPY --from=builder /install /usr/local
COPY Cortex/. ./
ENV PYTHONPATH=/app \
    PYTHONUNBUFFERED=1 \
    PYTHONDONTWRITEBYTECODE=1
RUN useradd -m -u 1000 appuser && \
    chown -R appuser:appuser /app
USER 1000
EXPOSE 8080
CMD ["sh", "-c", "uvicorn main:app --host 0.0.0.0 --port 8080 --workers ${WORKERS_COUNT} --no-access-log"]