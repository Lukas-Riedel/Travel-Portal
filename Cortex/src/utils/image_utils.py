import requests
import base64
from PIL import Image
from io import BytesIO
from typing import Optional
from src.core.logger import logger

def get_thumbnail_size() -> tuple[int, int]:
    return (512, 512)

def get_thumbnail(url: str) -> Optional[Image.Image]:
    try:
        r = requests.get(url)
        img = Image.open(BytesIO(r.content)).convert("RGB")
        width, height = img.size
        if height > width:
            return None
        img.thumbnail(get_thumbnail_size(), Image.Resampling.LANCZOS)
        return img    
    except Exception as e:
        logger.error(
            f"An error occurred when processing '{url}': {e}"
        )
        return None

def get_thumbnail_image(base64_data: str) -> Image.Image:
        data = base64.b64decode(base64_data)
        img = Image.open(BytesIO(data)).convert("RGB")
        width, height = img.size
        img.thumbnail(get_thumbnail_size(), Image.Resampling.LANCZOS)
        return img