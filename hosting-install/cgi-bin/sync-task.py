#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
TaskFlow - Sync Task Handler
CGI обертка для shared hosting
"""

import sys
import os
import json

# ========================================
# НАСТРОЙКИ - ЗАМЕНИТЕ НА СВОИ ПУТИ
# ========================================
BACKEND_PATH = '/home/username/backend'
BOT_TOKEN = 'YOUR_TELEGRAM_BOT_TOKEN'
DATABASE_URL = 'postgresql://user:pass@localhost:5432/taskflow_db'

sys.path.insert(0, BACKEND_PATH)
sys.path.insert(0, os.path.join(BACKEND_PATH, 'sync-task'))

os.environ['TELEGRAM_BOT_TOKEN'] = BOT_TOKEN
os.environ['DATABASE_URL'] = DATABASE_URL

# ========================================
# ЗАГРУЗКА HANDLER
# ========================================
def load_handler():
    import importlib.util
    module_path = os.path.join(BACKEND_PATH, 'sync-task', 'index.py')
    spec = importlib.util.spec_from_file_location("sync_task", module_path)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module.handler

# ========================================
# CGI HELPERS
# ========================================
def get_cgi_event():
    method = os.environ.get('REQUEST_METHOD', 'POST')
    content_length = int(os.environ.get('CONTENT_LENGTH', 0))
    body = sys.stdin.read(content_length) if content_length > 0 else ''
    
    headers = {}
    for key, value in os.environ.items():
        if key.startswith('HTTP_'):
            header_name = key[5:].replace('_', '-').lower()
            headers[header_name] = value
    
    query_params = {}
    query_string = os.environ.get('QUERY_STRING', '')
    if query_string:
        for param in query_string.split('&'):
            if '=' in param:
                key, value = param.split('=', 1)
                query_params[key] = value
    
    return {
        'httpMethod': method,
        'headers': headers,
        'url': os.environ.get('REQUEST_URI', ''),
        'params': {},
        'queryStringParameters': query_params,
        'multiValueParams': {},
        'multiValueHeaders': {},
        'multiValueQueryStringParameters': {},
        'pathParams': {},
        'body': body,
        'isBase64Encoded': False,
        'requestContext': {
            'requestId': os.environ.get('UNIQUE_ID', 'cgi-request'),
            'identity': {
                'sourceIp': os.environ.get('REMOTE_ADDR', 'unknown'),
                'userAgent': os.environ.get('HTTP_USER_AGENT', '')
            },
            'httpMethod': method,
            'requestTime': '',
            'requestTimeEpoch': 0
        }
    }

class MockContext:
    def __init__(self):
        self.request_id = os.environ.get('UNIQUE_ID', 'cgi-request')
        self.function_name = 'sync-task'
        self.function_version = '1.0.0'
        self.memory_limit_in_mb = 256
        self.function_folder_id = 'local'
        self.deadline_ms = None
        self.token = None

def output_response(result):
    status_code = result.get('statusCode', 200)
    headers = result.get('headers', {})
    body = result.get('body', '')
    
    print(f"Status: {status_code}")
    print("Access-Control-Allow-Origin: *")
    print("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS")
    print("Access-Control-Allow-Headers: Content-Type, X-User-Id, X-Auth-Token")
    
    for key, value in headers.items():
        if key.lower() not in ['access-control-allow-origin', 'access-control-allow-methods', 'access-control-allow-headers']:
            print(f"{key}: {value}")
    
    print()
    print(body)

# ========================================
# MAIN
# ========================================
try:
    handler = load_handler()
    event = get_cgi_event()
    context = MockContext()
    result = handler(event, context)
    output_response(result)
except Exception as e:
    print("Status: 500")
    print("Content-Type: application/json")
    print("Access-Control-Allow-Origin: *")
    print()
    print(json.dumps({
        'error': 'Internal Server Error',
        'message': str(e),
        'type': type(e).__name__
    }))
