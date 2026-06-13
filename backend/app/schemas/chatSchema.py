"""Chat message schemas."""

from datetime import datetime
from pydantic import BaseModel, ConfigDict, Field


class ReviewEnum(str):
    NOTHING = "0"
    LIKE = "1"
    DISLIKE = "2"


class ChatMessageCreate(BaseModel):
    session_id: str = Field(default="", max_length=12)
    conversation: str = Field(default="")
    review: str = Field(default=ReviewEnum.NOTHING, max_length=1)


class ChatMessageUpdate(BaseModel):
    review: str = Field(default=ReviewEnum.NOTHING, max_length=1)


class ChatMessageRead(BaseModel):
    id: int
    company_id: int
    session_id: str
    conversation: str
    review: str
    ip: str | None = None
    token_consume: int = 0
    model_name: str | None = None
    created_at: datetime
    updated_at: datetime

    model_config = ConfigDict(from_attributes=True)


class ChatSessionPage(BaseModel):
    items: list[ChatMessageRead]
    total: int
    page: int
    page_size: int
    total_pages: int


ChatMessage = ChatMessageRead
