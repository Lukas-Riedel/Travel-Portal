from typing import Any, Final, Literal

import requests

REQUEST_TIMEOUT: Final[tuple[Literal[5], Literal[15]]] = (5, 15)


class PistonClient:
    def __init__(self, piston_host: str, piston_port: int) -> None:
        self.base_url: str = f"http://{piston_host}:{piston_port}"

    def execute_python_code(self, code: str) -> dict[str, Any]:
        response = requests.post(
            url=f"{self.base_url}/api/v2/execute",
            json={
                "language": "python",
                "version": "*",
                "files": [{"content": code}],
            },
            timeout=REQUEST_TIMEOUT,
        )
        if response.status_code != 200:
            raise RuntimeError(
                f"Could not execute Python code. Reason: {response.text}"
            )

        data = response.json()
        run = data.get("run")
        if not isinstance(run, dict):
            raise RuntimeError("Piston response did not contain a run result.")

        return {
            "success": run.get("code") == 0,
            "code": run.get("code"),
            "output": run.get("output", ""),
            "stdout": run.get("stdout", ""),
            "stderr": run.get("stderr", ""),
            "signal": run.get("signal"),
            "status": run.get("status"),
            "message": run.get("message"),
            "version": data.get("version"),
        }
