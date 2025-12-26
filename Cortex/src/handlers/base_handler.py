from abc import ABC, abstractmethod
from typing import List

class BaseHandler(ABC):
    @abstractmethod
    def handle(self, args: dict) -> None:
        pass
    
    @abstractmethod
    def get_handled_event_names(self) -> List[str]:
        pass