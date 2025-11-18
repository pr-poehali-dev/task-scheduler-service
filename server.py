"""
TaskFlow Backend API Gateway
Объединяет все backend функции в единый API
"""

from fastapi import FastAPI, Request, Response
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
import importlib.util
import sys
import json
import os
from typing import Dict, Any
import logging

# Настройка логирования
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

app = FastAPI(
    title="TaskFlow Backend API",
    description="API Gateway для серверных функций TaskFlow",
    version="1.0.0"
)

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Функция для динамической загрузки Python модулей
def load_function_handler(function_name: str):
    """
    Загружает handler функцию из backend модуля
    
    Args:
        function_name: имя функции (telegram-bot, notify-task и т.д.)
    
    Returns:
        Функция handler из модуля
    """
    module_path = f"/app/backend/{function_name}/index.py"
    
    if not os.path.exists(module_path):
        logger.error(f"Module not found: {module_path}")
        raise FileNotFoundError(f"Module {function_name} not found")
    
    spec = importlib.util.spec_from_file_location(function_name, module_path)
    module = importlib.util.module_from_spec(spec)
    sys.modules[function_name] = module
    spec.loader.exec_module(module)
    
    logger.info(f"Loaded function handler: {function_name}")
    return module.handler

# Загрузка всех handlers при старте приложения
try:
    telegram_bot_handler = load_function_handler("telegram-bot")
    notify_task_handler = load_function_handler("notify-task")
    sync_task_handler = load_function_handler("sync-task")
    save_task_handler = load_function_handler("save-task")
    logger.info("All function handlers loaded successfully")
except Exception as e:
    logger.error(f"Failed to load function handlers: {e}")
    raise

# Mock context объект для имитации Cloud Function Context
class MockContext:
    """Имитация контекста Cloud Function"""
    def __init__(self):
        self.request_id = "local-request"
        self.function_name = "local-function"
        self.function_version = "1.0.0"
        self.memory_limit_in_mb = 256
        self.function_folder_id = "local"
        self.deadline_ms = None
        self.token = None

async def create_event(request: Request) -> Dict[str, Any]:
    """
    Преобразует FastAPI Request в формат Cloud Function Event
    
    Args:
        request: FastAPI Request объект
    
    Returns:
        Dict в формате Cloud Function Event
    """
    body = await request.body()
    
    return {
        "httpMethod": request.method,
        "headers": dict(request.headers),
        "url": str(request.url),
        "params": {},
        "queryStringParameters": dict(request.query_params) if request.query_params else {},
        "multiValueParams": {},
        "multiValueHeaders": {},
        "multiValueQueryStringParameters": {},
        "pathParams": {},
        "body": body.decode('utf-8') if body else "",
        "isBase64Encoded": False,
        "requestContext": {
            "requestId": request.headers.get("x-request-id", "unknown"),
            "identity": {
                "sourceIp": request.client.host if request.client else "unknown",
                "userAgent": request.headers.get("user-agent", "")
            },
            "httpMethod": request.method,
            "requestTime": "",
            "requestTimeEpoch": 0
        }
    }

def create_response(result: Dict[str, Any]) -> Response:
    """
    Преобразует Cloud Function Response в FastAPI Response
    
    Args:
        result: Dict с результатом от Cloud Function handler
    
    Returns:
        FastAPI Response объект
    """
    headers = result.get("headers", {})
    
    # Убедимся, что CORS заголовки присутствуют
    if "Access-Control-Allow-Origin" not in headers:
        headers["Access-Control-Allow-Origin"] = "*"
    
    return Response(
        content=result.get("body", ""),
        status_code=result.get("statusCode", 200),
        headers=headers,
        media_type=headers.get("Content-Type", "application/json")
    )

# API Endpoints

@app.api_route("/api/telegram-bot", methods=["GET", "POST", "PUT", "DELETE", "OPTIONS"])
async def telegram_bot(request: Request):
    """Telegram webhook endpoint"""
    try:
        event = await create_event(request)
        context = MockContext()
        result = telegram_bot_handler(event, context)
        return create_response(result)
    except Exception as e:
        logger.error(f"Error in telegram-bot: {e}", exc_info=True)
        return JSONResponse(
            status_code=500,
            content={"error": "Internal server error", "message": str(e)}
        )

@app.api_route("/api/notify-task", methods=["GET", "POST", "PUT", "DELETE", "OPTIONS"])
async def notify_task(request: Request):
    """Task notification endpoint"""
    try:
        event = await create_event(request)
        context = MockContext()
        result = notify_task_handler(event, context)
        return create_response(result)
    except Exception as e:
        logger.error(f"Error in notify-task: {e}", exc_info=True)
        return JSONResponse(
            status_code=500,
            content={"error": "Internal server error", "message": str(e)}
        )

@app.api_route("/api/sync-task", methods=["GET", "POST", "PUT", "DELETE", "OPTIONS"])
async def sync_task(request: Request):
    """Task synchronization endpoint"""
    try:
        event = await create_event(request)
        context = MockContext()
        result = sync_task_handler(event, context)
        return create_response(result)
    except Exception as e:
        logger.error(f"Error in sync-task: {e}", exc_info=True)
        return JSONResponse(
            status_code=500,
            content={"error": "Internal server error", "message": str(e)}
        )

@app.api_route("/api/save-task", methods=["GET", "POST", "PUT", "DELETE", "OPTIONS"])
async def save_task(request: Request):
    """Task saving endpoint"""
    try:
        event = await create_event(request)
        context = MockContext()
        result = save_task_handler(event, context)
        return create_response(result)
    except Exception as e:
        logger.error(f"Error in save-task: {e}", exc_info=True)
        return JSONResponse(
            status_code=500,
            content={"error": "Internal server error", "message": str(e)}
        )

@app.get("/health")
async def health():
    """Health check endpoint"""
    return {
        "status": "ok",
        "service": "taskflow-backend",
        "version": "1.0.0",
        "functions": [
            "telegram-bot",
            "notify-task",
            "sync-task",
            "save-task"
        ]
    }

@app.get("/")
async def root():
    """Root endpoint"""
    return {
        "message": "TaskFlow Backend API",
        "version": "1.0.0",
        "endpoints": {
            "telegram_bot": "/api/telegram-bot",
            "notify_task": "/api/notify-task",
            "sync_task": "/api/sync-task",
            "save_task": "/api/save-task",
            "health": "/health"
        }
    }

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(
        app,
        host="0.0.0.0",
        port=8000,
        log_level="info"
    )
