FROM python:3.11-slim as builder
WORKDIR /app
COPY Cortex/requirements.txt ./
RUN pip install --no-cache-dir --prefix=/install -r requirements.txt
FROM python:3.11-slim
WORKDIR /app
COPY --from=builder /install /usr/local
COPY Cortex/. ./
ENV PYTHONPATH=/app
CMD ["python", "main.py"]