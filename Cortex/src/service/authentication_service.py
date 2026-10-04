import time
import requests
from fastapi import HTTPException, status
from jose import jwk, jwt
from jose.utils import base64url_decode
from src.core.logger import logger


class AuthenticationService:
    def __init__(
        self,
        iam_host: str,
        iam_port: int,
        iam_app_client_id: str,
        iam_backend_client_id: str,
        iam_backend_client_secret: str,
    ):
        self.iam_base_url = f"http://{iam_host}:{iam_port}"
        self.iam_app_client_id = iam_app_client_id
        self.iam_backend_client_id = iam_backend_client_id
        self.iam_backend_client_secret = iam_backend_client_secret
        self.jwks_endpoint = f"{self.iam_base_url}/certificates/jwks"
        self.token_endpoint = f"{self.iam_base_url}/token"
        self.cached_keys = None
        self.cached_service_token: str | None = None
        self.service_token_expires_at: float = 0.0

    def get_service_access_token(self) -> str:
        current_time = time.time()
        if self.cached_service_token is not None and current_time < self.service_token_expires_at:
            return self.cached_service_token

        payload = {
            "clientId": self.iam_backend_client_id,
            "clientSecret": self.iam_backend_client_secret,
        }

        try:
            response = requests.post(self.token_endpoint, json=payload, headers={"Content-Type": "application/json"}, timeout=10)

            data = response.json()
            access_token = data.get("accessToken")
            expires_in = data.get("expiresIn")

            self.cached_service_token = access_token
            # Cache for 80% of the expiration duration to avoid edge-of-expiry issues
            self.service_token_expires_at = current_time + (expires_in * 0.8)
            return access_token
        except Exception as exc:
            raise

    def get_jwks_keys(self):
        if self.cached_keys is None:
            response = requests.get(self.jwks_endpoint)
            if response.status_code != 200:
                raise RuntimeError(f"Could not fetch JWKS. Reason: {response.text}")

            self.cached_keys = response.json()

        return self.cached_keys

    def authenticate(self, token: str):
        try:
            jwks = self.get_jwks_keys()
            unverified_header = jwt.get_unverified_header(token)
            kid = unverified_header.get("kid")

            key_index = -1
            for i, key in enumerate(jwks["keys"]):
                if key["kid"] == kid:
                    key_index = i
                    break

            if key_index == -1:
                raise RuntimeError("Could not find key in JWKS.")

            public_key = jwk.construct(jwks["keys"][key_index])
            message, encoded_sig = token.rsplit(".", 1)
            decoded_sig = base64url_decode(encoded_sig.encode("utf-8"))

            if not public_key.verify(message.encode("utf-8"), decoded_sig):
                raise RuntimeError("The JWT token could not be verified.")

            claims = jwt.get_unverified_claims(token)

            return {
                "user_id": claims.get("sub"),
                "client": claims.get("azp"),
                "roles": claims.get("resource_access", {}).get(self.iam_app_client_id, {}).get("roles", [])
            }
        except Exception as e:
            raise HTTPException(
                status_code=status.HTTP_401_UNAUTHORIZED,
                detail=f"An error occurred when decoding JWT token. Reason: {str(e)}"
            )
