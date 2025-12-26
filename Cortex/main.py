import os
from dotenv import load_dotenv
from src.core.listener import CortexListener
from src.core.core_client import CoreClient
from src.core.distributed_cache import DistributedCache
from src.core.ai_engine import AiEngine
from src.handlers.highlights_selecting_triggered_handler import HighlightsSelectingTriggeredHandler

load_dotenv()


def main():
    core_client = CoreClient(
        os.getenv("CORE_BASE_URL"),
        os.getenv("IAM_BASE_URL"),
        os.getenv("IAM_BACKEND_CLIENT_ID"),
        os.getenv("IAM_BACKEND_CLIENT_SECRET"),
    )
    distributed_cache = DistributedCache(
        os.getenv("REDIS_HOST"),
        int(os.getenv("REDIS_PORT")),
        os.getenv("REDIS_PASSWORD"),
        os.getenv("REDIS_SSL", "false").lower() == "true"
    )
    ai_engine = AiEngine(
        distributed_cache,
        float(os.getenv("CONTENT_COEFFICIENT")),
        float(os.getenv("NEGATIVE_COEFFICIENT")),
    )

    handlers = [
        HighlightsSelectingTriggeredHandler(
            ai_engine,
            core_client,
            int(os.getenv("MAX_THREADS")),
            float(os.getenv("AGE_COEFFICIENT")),
            float(os.getenv("SIMILARITY_THRESHOLD")),
        )
    ]

    listener = CortexListener(
        handlers,
        os.getenv("RMQ_HOST"),
        int(os.getenv("RMQ_PORT")),
        os.getenv("RMQ_VHOST"),
        os.getenv("RMQ_USER"),
        os.getenv("RMQ_PASSWORD"),        
        os.getenv("RMQ_SSL", "false").lower() == "true",
        int(os.getenv("RMQ_HEARTBEAT")),
        os.getenv("CORTEX_QUEUE_NAME"),
    )
    listener.listen()


if __name__ == "__main__":
    main()
