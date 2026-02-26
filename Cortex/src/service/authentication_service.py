import os
import requests
from jose import jwt, jwk
from jose.utils import base64url_decode
from fastapi import Request, HTTPException, status
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials

class AuthenticationService:
    def __init__(self, iam_host: str, iam_port: str, iam_app_client_id: str):
        self.iam_base_url = f"http://{iam_host}:{iam_port}"
        self.iam_app_client_id = iam_app_client_id
        self.jwks_endpoint = f"{self.iam_base_url}/certificates/jwks"
        self._cached_keys = None

    def get_jwks_keys(self):
        if self._cached_keys is None:
            response = requests.get(self.jwks_endpoint)
            if response.status_code != 200:
                raise RuntimeError(f"Could not fetch JWKS. Reason: {response.text}")

            self._cached_keys = response.json()

        return self._cached_keys

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