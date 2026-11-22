from fastapi import FastAPI, HTTPException
from sqlalchemy import text
from sqlalchemy.exc import SQLAlchemyError
from .database import engine

app = FastAPI(title="StyloMate API", version="0.1.0")
from app.routers.api import router
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from starlette.exceptions import HTTPException as StarletteHTTPException
app.include_router(router)

@app.exception_handler(StarletteHTTPException)
async def http_error(request, exc):
    return JSONResponse({'error': {'message': exc.detail}}, status_code=exc.status_code, headers=exc.headers)

@app.exception_handler(RequestValidationError)
async def validation_error(request, exc):
    # Do not echo passwords, tokens or uploaded data in validation responses.
    fields = [{'field': '.'.join(str(p) for p in error['loc'][1:]), 'message': error['msg']} for error in exc.errors()]
    return JSONResponse({'error': {'message': 'Validation failed', 'fields': fields}}, status_code=422)

@app.get("/")
def root():
    return {"message": "Welcome to StyloMate API"}

@app.get("/api/health")
def health():
    return {"status": "ok"}

@app.get("/api/health/db")
def database_health():
    try:
        with engine.connect() as conn:
            conn.execute(text("SELECT 1"))
    except SQLAlchemyError:
        raise HTTPException(status_code=503, detail="Database unavailable")
    return {"status": "ok"}
