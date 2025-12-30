import redis
import json
from typing import Any, Optional


class DistributedCache:
    def __init__(self, redis_host: str, redis_port: int, redis_password: str, redis_ssl: bool) -> None:
        self._client = redis.Redis(
            host=redis_host, port=redis_port, password=redis_password, decode_responses=False, ssl=redis_ssl
        )

    def get(self, key: str, new_ttl: Optional[int] = None) -> Any:
        value = self._client.get(key)
        if value is None:
            return None

        if new_ttl is not None:
            self._client.expire(key, new_ttl)


        try:
            decoded = value.decode("utf-8")
            try:
                return json.loads(decoded)
            except json.JSONDecodeError:
                return decoded
        except UnicodeDecodeError:
            return value

    def set(self, key: str, value: Any, ttl: int) -> None:
        prepared_value = json.dumps(value) if isinstance(value, (dict, list)) else value
        self._client.set(key, prepared_value, ex=ttl)

    def try_set(self, key: str, value: Any, ttl: int) -> bool:
        prepared_value = json.dumps(value) if isinstance(value, (dict, list)) else value
        return bool(self._client.set(key, prepared_value, ex=ttl, nx=True))

    def delete(self, key: str) -> None:
        self._client.delete(key)
